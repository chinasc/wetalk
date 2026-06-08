<?php
namespace app\controller;

use app\model\Conversation;
use app\model\Message;
use app\model\Setting;
use app\service\WsService;
use support\Request;

class ChatController
{
    public function verifyAccessCode(Request $request)
    {
        $code = trim($request->post('code', ''));
        $accessCode = Setting::get('chat_access_code', '8888');
        if ($code !== $accessCode) {
            return json(['code' => 1, 'msg' => '验证码错误']);
        }
        return json(['code' => 0, 'msg' => '验证成功']);
    }

    public function conversations(Request $request)
    {
        $user = $request->user;
        $isCs = ($user->role === 'customer_service' || $user->role === 'admin');

        if ($isCs) {
            $query = Conversation::where('customer_service_id', $user->id)
                ->with(['user', 'latestMessage'])
                ->orderByRaw('cs_pinned DESC, updated_at DESC');
        } else {
            $query = Conversation::where('user_id', $user->id)
                ->with(['customerService', 'latestMessage'])
                ->orderBy('updated_at', 'desc');
        }

        $paginator = $query->paginate(20);

        $items = $paginator->items();
        $mapped = array_map(function ($conv) use ($user, $isCs) {
            $lastMsg = $conv->latestMessage;

            if (isset($conv->customerService) && $conv->customerService) {
                $peerNickname = $conv->customerService->nickname ?: $conv->customerService->username;
                $peerAvatar = $conv->customerService->avatar;
            } elseif (isset($conv->user) && $conv->user) {
                $peerNickname = $conv->user->nickname ?: $conv->user->username;
                $peerAvatar = $conv->user->avatar;
            } else {
                $peerNickname = '';
                $peerAvatar = '';
            }

            $lastReadId = $isCs ? $conv->cs_last_read_id : $conv->user_last_read_id;
            $unreadCount = Message::where('conversation_id', $conv->id)
                ->where('id', '>', $lastReadId)
                ->where('sender_id', '!=', $user->id)
                ->count();

            return [
                'id' => $conv->id,
                'user_id' => $conv->user_id,
                'user_nickname' => $conv->user ? ($conv->user->nickname ?: $conv->user->username) : '',
                'user_avatar' => $conv->user ? $conv->user->avatar : '',
                'user_online' => $conv->user ? $conv->user->isOnline() : false,
                'customer_service_id' => $conv->customer_service_id,
                'customer_service_name' => $conv->customerService ? ($conv->customerService->nickname ?: $conv->customerService->username) : '',
                'customer_service_avatar' => $conv->customerService ? $conv->customerService->avatar : '',
                'last_message' => $lastMsg ? $lastMsg->content : '',
                'last_message_type' => $lastMsg ? $lastMsg->type : '',
                'last_message_time' => $lastMsg ? $lastMsg->created_at : $conv->created_at,
                'unread_count' => $unreadCount,
                'status' => $conv->status,
                'cs_pinned' => (bool)$conv->cs_pinned,
                'created_at' => $conv->created_at,
                'updated_at' => $conv->updated_at,
            ];
        }, $items);

        return json([
            'code' => 0,
            'data' => $mapped,
        ]);
    }

    public function createConversation(Request $request)
    {
        $user = $request->user;
        $csId = (int)$request->post('customer_service_id', 0);

        if (!$csId) {
            return json(['code' => 1, 'msg' => '请选择客服']);
        }

        $existing = Conversation::where('user_id', $user->id)
            ->where('customer_service_id', $csId)
            ->where('status', 'active')
            ->first();

        if ($existing) {
            return json(['code' => 0, 'data' => $existing->load(['customerService', 'latestMessage'])]);
        }

        $conversation = Conversation::create([
            'user_id' => $user->id,
            'customer_service_id' => $csId,
            'status' => 'active',
        ]);

        return json(['code' => 0, 'data' => $conversation->load(['customerService'])]);
    }

    public function csCreateConversation(Request $request, $id)
    {
        $cs = $request->user;
        $userId = (int)$id;

        if (!$userId) {
            return json(['code' => 1, 'msg' => '请选择用户']);
        }

        $targetUser = \app\model\User::find($userId);
        if (!$targetUser || $targetUser->role !== 'user') {
            return json(['code' => 1, 'msg' => '用户不存在']);
        }

        $existing = Conversation::where('user_id', $userId)
            ->where('customer_service_id', $cs->id)
            ->where('status', 'active')
            ->first();

        if ($existing) {
            return json(['code' => 0, 'data' => $existing]);
        }

        $conversation = Conversation::create([
            'user_id' => $userId,
            'customer_service_id' => $cs->id,
            'status' => 'active',
        ]);

        return json(['code' => 0, 'data' => $conversation]);
    }

    public function messages(Request $request, $conversationId)
    {
        $conversation = Conversation::find($conversationId);
        if (!$conversation) {
            return json(['code' => 1, 'msg' => '会话不存在']);
        }

        $user = $request->user;
        $isMember = ($user->id === $conversation->user_id || $user->id === $conversation->customer_service_id);
        if (!$isMember && $user->role !== 'admin') {
            return json(['code' => 403, 'msg' => '无权限']);
        }

        $beforeId = (int)$request->get('before_id', 0);
        $query = Message::where('conversation_id', $conversationId);
        if ($beforeId) {
            $query->where('id', '<', $beforeId);
        }
        $messages = $query->orderBy('id', 'desc')->limit(30)->get()->reverse()->values();

        // Attach sender nickname for admin view
        $mapped = $messages->map(function ($msg) {
            return [
                'id' => $msg->id,
                'conversation_id' => $msg->conversation_id,
                'sender_id' => $msg->sender_id,
                'sender_type' => $msg->sender_type,
                'sender_nickname' => $msg->sender ? ($msg->sender->nickname ?: $msg->sender->username) : '',
                'message_type' => $msg->type,
                'type' => $msg->type,
                'content' => $msg->content,
                'created_at' => $msg->created_at,
            ];
        });

        return json(['code' => 0, 'data' => $mapped]);
    }

    public function sendMessage(Request $request)
    {
        $user = $request->user;
        $conversationId = (int)$request->post('conversation_id', 0);
        $type = $request->post('type', 'text');
        $content = $request->post('content', '');

        $conversation = Conversation::find($conversationId);
        if (!$conversation) {
            return json(['code' => 1, 'msg' => '会话不存在']);
        }

        $isUser = ($user->id === $conversation->user_id);
        $isCs = ($user->id === $conversation->customer_service_id);
        if (!$isUser && !$isCs) {
            return json(['code' => 403, 'msg' => '无权限']);
        }

        if ($type === 'image' && $request->file('file')) {
            $file = $request->file('file');
            $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/bmp'];
            $mime = $file->getUploadMimeType();
            if (!in_array($mime, $allowedMimes)) {
                return json(['code' => 1, 'msg' => '不支持的图片格式，请上传 JPG/PNG/GIF/WEBP']);
            }
            $ext = $file->getUploadExtension();
            $safeExts = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'];
            if (!in_array(strtolower($ext), $safeExts)) {
                $ext = 'jpg';
            }
            $filename = uniqid() . '.' . $ext;
            $file->move(public_path() . '/upload/chat/' . $filename);
            $content = '/upload/chat/' . $filename;
        }

        $now = date('Y-m-d H:i:s');
        $message = Message::create([
            'conversation_id' => $conversationId,
            'sender_id' => $user->id,
            'sender_type' => $isUser ? 'user' : 'customer_service',
            'type' => $type,
            'content' => $content,
            'created_at' => $now,
        ]);

        $conversation->touch();

        $targetId = $isUser ? $conversation->customer_service_id : $conversation->user_id;
        $msgData = [
            'id' => $message->id,
            'conversation_id' => $conversationId,
            'sender_id' => $user->id,
            'sender_type' => $message->sender_type,
            'sender_nickname' => $user->nickname,
            'sender_avatar' => $user->avatar,
            'type' => $type,
            'content' => $content,
            'created_at' => $now,
        ];

        WsService::sendToUser($targetId, [
            'event' => 'message.new',
            'data' => $msgData,
        ]);

        return json(['code' => 0, 'data' => $msgData]);
    }

    public function markRead(Request $request)
    {
        $conversationId = (int)$request->post('conversation_id', 0);
        $user = $request->user;

        $conversation = Conversation::find($conversationId);
        if (!$conversation) {
            return json(['code' => 1, 'msg' => '会话不存在']);
        }

        $maxId = Message::where('conversation_id', $conversationId)->max('id') ?: 0;
        if ($user->id === $conversation->user_id) {
            $conversation->update(['user_last_read_id' => $maxId]);
        } elseif ($user->id === $conversation->customer_service_id) {
            $conversation->update(['cs_last_read_id' => $maxId]);
        }

        $targetId = ($user->id === $conversation->user_id)
            ? $conversation->customer_service_id
            : $conversation->user_id;

        WsService::sendToUser($targetId, [
            'event' => 'message.read',
            'data' => [
                'conversation_id' => $conversationId,
                'reader_id' => $user->id,
            ],
        ]);

        return json(['code' => 0, 'msg' => '已标记已读']);
    }

    public function clearMessages(Request $request, $conversationId)
    {
        $conversation = Conversation::find($conversationId);
        if (!$conversation) {
            return json(['code' => 1, 'msg' => '会话不存在']);
        }

        $user = $request->user;
        $isMember = ($user->id === $conversation->user_id || $user->id === $conversation->customer_service_id);
        if (!$isMember && $user->role !== 'admin') {
            return json(['code' => 403, 'msg' => '无权限']);
        }

        Message::where('conversation_id', $conversationId)->delete();
        $conversation->update(['user_last_read_id' => 0, 'cs_last_read_id' => 0]);

        return json(['code' => 0, 'msg' => '聊天记录已清除']);
    }

    public function recallMessage(Request $request)
    {
        $messageId = (int)$request->post('message_id', 0);
        $message = Message::find($messageId);
        if (!$message) {
            return json(['code' => 1, 'msg' => '消息不存在']);
        }

        $user = $request->user;
        if ($message->sender_id !== $user->id) {
            return json(['code' => 403, 'msg' => '只能撤回自己发送的消息']);
        }

        $message->type = 'system';
        $message->content = '消息已撤回';
        $message->save();

        $conversation = Conversation::find($message->conversation_id);
        if ($conversation) {
            $targetId = ($user->id === $conversation->user_id)
                ? $conversation->customer_service_id
                : $conversation->user_id;
            WsService::sendToUser($targetId, [
                'event' => 'message.recalled',
                'data' => [
                    'id' => $message->id,
                    'conversation_id' => $message->conversation_id,
                    'type' => 'system',
                    'content' => '消息已撤回',
                ],
            ]);
        }

        return json(['code' => 0, 'msg' => '已撤回']);
    }

    public function deleteMessage(Request $request)
    {
        $messageId = (int)$request->post('message_id', 0);
        $message = Message::find($messageId);
        if (!$message) {
            return json(['code' => 1, 'msg' => '消息不存在']);
        }

        $user = $request->user;
        $conversation = Conversation::find($message->conversation_id);
        $isMember = $conversation && ($user->id === $conversation->user_id || $user->id === $conversation->customer_service_id);
        if (!$isMember && $user->role !== 'admin') {
            return json(['code' => 403, 'msg' => '无权限']);
        }

        $message->delete();

        return json(['code' => 0, 'msg' => '已删除']);
    }

    public function deleteConversation(Request $request, $conversationId)
    {
        $conversation = Conversation::find($conversationId);
        if (!$conversation) {
            return json(['code' => 1, 'msg' => '会话不存在']);
        }

        $user = $request->user;
        $isMember = ($user->id === $conversation->user_id || $user->id === $conversation->customer_service_id);
        if (!$isMember && $user->role !== 'admin') {
            return json(['code' => 403, 'msg' => '无权限']);
        }

        Message::where('conversation_id', $conversationId)->delete();
        $conversation->delete();

        return json(['code' => 0, 'msg' => '会话已删除']);
    }

    public function togglePin(Request $request, $conversationId)
    {
        $conversation = Conversation::find($conversationId);
        if (!$conversation) {
            return json(['code' => 1, 'msg' => '会话不存在']);
        }

        $user = $request->user;
        if ($user->id !== $conversation->customer_service_id && $user->role !== 'admin') {
            return json(['code' => 403, 'msg' => '无权限']);
        }

        $conversation->cs_pinned = $conversation->cs_pinned ? 0 : 1;
        $conversation->save();

        return json([
            'code' => 0,
            'msg' => $conversation->cs_pinned ? '已置顶' : '已取消置顶',
            'data' => ['cs_pinned' => (bool)$conversation->cs_pinned],
        ]);
    }

    public function adminConversations(Request $request)
    {
        $csId = $request->get('customer_service_id');
        $userId = $request->get('user_id');

        $query = Conversation::with(['user', 'customerService', 'latestMessage']);
        if ($csId) {
            $query->where('customer_service_id', $csId);
        }
        if ($userId) {
            $query->where('user_id', $userId);
        }

        $paginator = $query->orderBy('updated_at', 'desc')->paginate(20);
        $items = $paginator->items();

        $mapped = array_map(function ($conv) {
            $lastMsg = $conv->latestMessage;
            return [
                'id' => $conv->id,
                'user_id' => $conv->user_id,
                'user_name' => $conv->user ? ($conv->user->nickname ?: $conv->user->username) : '',
                'user_avatar' => $conv->user ? $conv->user->avatar : '',
                'customer_service_id' => $conv->customer_service_id,
                'cs_name' => $conv->customerService ? ($conv->customerService->nickname ?: $conv->customerService->username) : '',
                'cs_avatar' => $conv->customerService ? $conv->customerService->avatar : '',
                'last_message' => $lastMsg ? $lastMsg->content : '',
                'last_time' => $lastMsg ? $lastMsg->created_at : $conv->created_at,
                'status' => $conv->status,
                'created_at' => $conv->created_at,
                'updated_at' => $conv->updated_at,
            ];
        }, $items);

        return json([
            'code' => 0,
            'data' => [
                'data' => $mapped,
                'total' => $paginator->total(),
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
            ],
        ]);
    }
}
