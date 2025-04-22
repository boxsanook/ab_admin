<div class="modal fade" id="insertModal" tabindex="-1" role="dialog" aria-labelledby="insertModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="insertModalLabel">Add User</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="insertForm">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="insertUserId">User ID</label>
                                    <input type="text" class="form-control" id="insertUserId" name="user_id" required>
                                </div>
                                <div class="form-group">
                                    <label for="insertEmail">Email</label>
                                    <input type="email" class="form-control" id="insertEmail" name="email" required>
                                </div>
                                <div class="form-group">
                                    <label for="insertName">Name</label>
                                    <input type="text" class="form-control" id="insertName" name="name" required>
                                </div>
                                <div class="form-group">
                                    <label for="insertActive">Active</label>
                                    <select class="form-control" id="insertActive" name="active">
                                        <option value="1">Yes</option>
                                        <option value="0">No</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="insertIsAdmin">Admin</label>
                                    <select class="form-control" id="insertIsAdmin" name="is_admin">
                                        <option value="1">Yes</option>
                                        <option value="0">No</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="insertDisplayName">Display Name</label>
                                    <input type="text" class="form-control" id="insertDisplayName" name="display_name">
                                </div>
                                <div class="form-group">
                                    <label for="insertPictureUrl">Picture URL</label>
                                    <input type="url" class="form-control" id="insertPictureUrl" name="picture_url">
                                </div>
                                <div class="form-group">
                                    <label for="insertStatusMessage">Status Message</label>
                                    <textarea class="form-control" id="insertStatusMessage"
                                        name="status_message"></textarea>
                                </div>
                            </div>
                            <div class="col-md-6">

                                <div class="form-group">
                                    <label for="insertAccessToken">Access Token</label>
                                    <input type="text" class="form-control" id="insertAccessToken" name="access_token">
                                </div>
                                <div class="form-group">
                                    <label for="insertRefreshToken">Refresh Token</label>
                                    <input type="text" class="form-control" id="insertRefreshToken"
                                        name="refresh_token">
                                </div>
                                <div class="form-group">
                                    <label for="insertTokenExpiresAt">Token Expires At</label>
                                    <input type="datetime-local" class="form-control" id="insertTokenExpiresAt"
                                        name="token_expires_at">
                                </div>
                                <div class="form-group">
                                    <label for="insertNotifyBy">Notify By</label>
                                    <select class="form-control" id="insertNotifyBy" name="notify_by">
                                        <option value="line">Line</option>
                                        <option value="telegram">Telegram</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="insertTelegramTokenId">Telegram Token ID</label>
                                    <input type="text" class="form-control" id="insertTelegramTokenId"
                                        name="telegram_token_id">
                                </div>
                                <div class="form-group">
                                    <label for="insertTelegramChatId">Telegram Chat ID</label>
                                    <input type="text" class="form-control" id="insertTelegramChatId"
                                        name="telegram_chat_id">
                                </div>
                                <div class="form-group">
                                    <label for="insertMaxProfile">Max Profile</label>
                                    <input type="text" class="form-control" id="insertMaxProfile" name="max_profile">
                                </div>
                                <div class="form-group">
                                    <label for="insertComputerId">Computer ID</label>
                                    <input type="text" class="form-control" id="insertComputerId" name="computer_id"
                                        required>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-success">Add User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>