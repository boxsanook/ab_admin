<div class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-labelledby="editModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editModalLabel">Edit User</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="editForm">
                    <div class="modal-body">
                        <input type="hidden" id="editUserId" name="id">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="editEmail">Email</label>
                                    <input type="email" class="form-control" id="editEmail" name="email" required>
                                </div>
                                <div class="form-group">
                                    <label for="editName">Name</label>
                                    <input type="text" class="form-control" id="editName" name="name" required>
                                </div>
                                <div class="form-group">
                                    <label for="editActive">Active</label>
                                    <select class="form-control" id="editActive" name="active">
                                        <option value="1">Yes</option>
                                        <option value="0">No</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="editIsAdmin">Admin</label>
                                    <select class="form-control" id="editIsAdmin" name="is_admin">
                                        <option value="1">Yes</option>
                                        <option value="0">No</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="editDisplayName">Display Name</label>
                                    <input type="text" class="form-control" id="editDisplayName" name="display_name">
                                </div>
                                <div class="form-group">
                                    <label for="editPictureUrl">Picture URL</label>
                                    <input type="url" class="form-control" id="editPictureUrl" name="picture_url">
                                </div>
                                <div class="form-group">
                                    <label for="editStatusMessage">Status Message</label>
                                    <textarea class="form-control" id="editStatusMessage"
                                        name="status_message"></textarea>
                                </div>
                                <div class="form-group">
                                    <label for="editAccessToken">Access Token</label>
                                    <input type="text" class="form-control" id="editAccessToken" name="access_token">
                                </div>
                                <div class="form-group">
                                    <label for="editMaxProfile">Max Profile</label>
                                    <input type="text" class="form-control" id="editMaxProfile" name="max_profile">
                                </div>
                            </div>
                            <div class="col-md-6">

                                <div class="form-group">
                                    <label for="editRefreshToken">Refresh Token</label>
                                    <input type="text" class="form-control" id="editRefreshToken" name="refresh_token">
                                </div>
                                <div class="form-group">
                                    <label for="editTokenExpiresAt">Token Expires At</label>
                                    <input type="datetime-local" class="form-control" id="editTokenExpiresAt"
                                        name="token_expires_at">
                                </div>
                                <div class="form-group">
                                    <label for="editNotifyBy">Notify By</label>
                                    <select class="form-control" id="editNotifyBy" name="notify_by">
                                        <option value="line">Line</option>
                                        <option value="telegram">Telegram</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="editTelegramTokenId">Telegram Token ID</label>
                                    <input type="text" class="form-control" id="editTelegramTokenId"
                                        name="telegram_token_id">
                                </div>
                                <div class="form-group">
                                    <label for="editTelegramChatId">Telegram Chat ID</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="editTelegramChatId"
                                            name="telegram_chat_id">
                                        <div class="input-group-append">
                                            <button type="button" class="btn btn-info" id="testTelegramBtn">Test Message</button>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="editComputerId">Computer ID</label>
                                    <input type="text" class="form-control" id="editComputerId" name="Computer_ID">
                                </div>
                                <div class="form-group">
                                    <label for="editAffiliate">Affiliate</label>
                                    <select class="form-control" id="editAffiliate" name="affiliate">
                                        <option value="0">No</option>
                                        <option value="1">Yes</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="editAffiliateCode">Affiliate Code</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="editAffiliateCode" name="affiliate_code">
                                        <div class="input-group-append">
                                            <button type="button" class="btn btn-info generate-code-btn" onclick="generateAffiliateCodeForField('#editAffiliateCode')">Generate</button>
                                            <button type="button" class="btn btn-success copy-link-btn">Copy Link</button>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="editReferralBy">Referral By</label>
                                    <input type="text" class="form-control" id="editReferralBy" name="referral_by">
                                </div>
                                <div class="form-group">
                                    <label for="editToken">Registration Token</label>
                                    <textarea class="form-control" id="editToken" name="token" rows="1"
                                        readonly></textarea>
                                </div>
                                <div class="form-group">
                                    <label for="editExpiresAt">Token Expires At</label>
                                    <input type="datetime-local" class="form-control" id="editExpiresAt"
                                        name="expires_at" readonly>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>