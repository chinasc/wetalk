<?php
use Webman\Route;

// 公开路由
Route::post('/api/auth/login', [app\controller\AuthController::class, 'login']);
Route::post('/api/auth/check-first-login', [app\controller\AuthController::class, 'checkFirstLogin']);
Route::post('/api/auth/admin-login', [app\controller\AuthController::class, 'adminLogin']);
Route::post('/api/auth/cs-login', [app\controller\AuthController::class, 'csLogin']);
Route::post('/api/chat/verify-code', [app\controller\ChatController::class, 'verifyAccessCode']);
Route::get('/api/games', [app\controller\GameController::class, 'index']);

// 需要登录的路由
Route::group('/api', function () {
    // 用户信息
    Route::get('/auth/info', [app\controller\AuthController::class, 'info']);
    Route::post('/auth/profile', [app\controller\AuthController::class, 'updateProfile']);

    // 客服列表（用户+客服可见）
    Route::get('/cs/list', [app\controller\CsController::class, 'index']);

    // 会话
    Route::get('/chat/conversations', [app\controller\ChatController::class, 'conversations']);
    Route::post('/chat/conversation/create', [app\controller\ChatController::class, 'createConversation']);
    Route::get('/chat/messages/{conversationId}', [app\controller\ChatController::class, 'messages']);
    Route::post('/chat/send', [app\controller\ChatController::class, 'sendMessage']);
    Route::post('/chat/mark-read', [app\controller\ChatController::class, 'markRead']);
    Route::post('/chat/recall', [app\controller\ChatController::class, 'recallMessage']);
    Route::post('/chat/delete-message', [app\controller\ChatController::class, 'deleteMessage']);
    Route::post('/chat/pin/{conversationId}', [app\controller\ChatController::class, 'togglePin']);
    Route::delete('/chat/messages/{conversationId}', [app\controller\ChatController::class, 'clearMessages']);
    Route::delete('/chat/conversation/{conversationId}', [app\controller\ChatController::class, 'deleteConversation']);

    // 文件上传
    Route::post('/upload/image', [app\controller\UploadController::class, 'image']);

    // 相册（客服端查看用）
    Route::get('/albums', [app\controller\AlbumController::class, 'index']);
    Route::get('/albums/{id}', [app\controller\AlbumController::class, 'show']);

    // 常用语（客服端查看用）
    Route::get('/quick-replies', [app\controller\QuickReplyController::class, 'index']);

    // 常用语管理（客服端）
    Route::group('/cs/quick-replies', function () {
        Route::post('', [app\controller\QuickReplyController::class, 'store']);
        Route::post('/{id}', [app\controller\QuickReplyController::class, 'update']);
        Route::delete('/{id}', [app\controller\QuickReplyController::class, 'destroy']);
    })->middleware([app\middleware\CsAuth::class]);

    // 用户管理（客服端）
    Route::group('/cs/users', function () {
        Route::get('', [app\controller\UserController::class, 'index']);
        Route::post('', [app\controller\UserController::class, 'store']);
        Route::post('/{id}/reset-password', [app\controller\UserController::class, 'resetPassword']);
        Route::post('/{id}', [app\controller\UserController::class, 'update']);
        Route::delete('/{id}', [app\controller\UserController::class, 'destroy']);
    })->middleware([app\middleware\CsAuth::class]);

    // 相册管理（客服端）
    Route::group('/cs/albums', function () {
        Route::post('', [app\controller\AlbumController::class, 'store']);
        Route::post('/{id}', [app\controller\AlbumController::class, 'update']);
        Route::delete('/{id}', [app\controller\AlbumController::class, 'destroy']);
        Route::post('/{id}/images', [app\controller\AlbumController::class, 'uploadImage']);
        Route::post('/images/{imageId}', [app\controller\AlbumController::class, 'updateImage']);
        Route::delete('/images/{imageId}', [app\controller\AlbumController::class, 'deleteImage']);
    })->middleware([app\middleware\CsAuth::class]);

    // 管理后台路由
    Route::group('/admin', function () {
        // 用户管理
        Route::get('/users', [app\controller\UserController::class, 'index']);
        Route::post('/users', [app\controller\UserController::class, 'store']);
        Route::post('/users/update/{id}', [app\controller\UserController::class, 'update']);
        Route::post('/users/reset-password/{id}', [app\controller\UserController::class, 'resetPassword']);
        Route::post('/users/toggle-status/{id}', [app\controller\UserController::class, 'toggleStatus']);
        Route::delete('/users/{id}', [app\controller\UserController::class, 'destroy']);

        // 客服管理
        Route::post('/cs/set', [app\controller\CsController::class, 'setCs']);
        Route::post('/cs/remove/{id}', [app\controller\CsController::class, 'removeCs']);

        // 相册管理
        Route::post('/albums', [app\controller\AlbumController::class, 'store']);
        Route::post('/albums/{id}', [app\controller\AlbumController::class, 'update']);
        Route::delete('/albums/{id}', [app\controller\AlbumController::class, 'destroy']);
        Route::post('/albums/{id}/images', [app\controller\AlbumController::class, 'uploadImage']);
        Route::post('/albums/images/{imageId}', [app\controller\AlbumController::class, 'updateImage']);
        Route::delete('/albums/images/{imageId}', [app\controller\AlbumController::class, 'deleteImage']);

        // 游戏管理
        Route::post('/games', [app\controller\GameController::class, 'store']);
        Route::post('/games/{id}', [app\controller\GameController::class, 'update']);
        Route::delete('/games/{id}', [app\controller\GameController::class, 'destroy']);

        // 聊天记录
        Route::get('/chat/conversations', [app\controller\ChatController::class, 'adminConversations']);

        // 常用语管理
        Route::get('/quick-replies', [app\controller\QuickReplyController::class, 'index']);
        Route::post('/quick-replies', [app\controller\QuickReplyController::class, 'store']);
        Route::post('/quick-replies/{id}', [app\controller\QuickReplyController::class, 'update']);
        Route::delete('/quick-replies/{id}', [app\controller\QuickReplyController::class, 'destroy']);

        // 系统设置
        Route::get('/settings', [app\controller\SettingsController::class, 'index']);
        Route::post('/settings', [app\controller\SettingsController::class, 'update']);
    })->middleware([app\middleware\AdminAuth::class]);
})->middleware([app\middleware\JwtAuth::class]);

// 关闭默认路由
Route::disableDefaultRoute();
