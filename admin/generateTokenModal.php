<div class="modal fade" id="generateTokenModal" tabindex="-1" role="dialog"
        aria-labelledby="generateTokenModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="generateTokenModalLabel">Generate Registration Token</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="generateTokenForm">
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="modal_user_id">User ID</label>
                            <input type="text" class="form-control" id="modal_user_id" name="user_id" readonly>
                        </div>
                        <div class="form-group">
                            <label for="modal_expiration_days">Expiration Days</label>
                            <select class="form-control" id="modal_expiration_days" name="expiration_days" required>
                                <option value="7">7 days</option>
                                <option value="14">14 days</option>
                                <option value="30" selected>30 days</option>
                                <option value="90">90 days</option>
                                <option value="180">180 days</option>
                                <option value="365">1 year</option>
                            </select>
                        </div>
                        <!-- Placeholder for the generated token -->
                        <div class="form-group">
                            <label for="generatedTokenDisplay">Generated Token</label>
                            <div class="input-group">
                                <textarea id="generatedTokenDisplay" class="form-control" rows="4" readonly></textarea>
                                <div class="input-group-append">
                                    <button id="copyTokenButton" class="btn btn-outline-secondary"
                                        type="button"><i class="fas fa-copy"></i> Copy</button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="alert alert-info mt-3">
                            <h5><i class="icon fas fa-info"></i> How to use the token</h5>
                            <p>This token can be used for registration or authentication. The token will expire after the selected number of days.</p>
                            <p>Share this token securely with the user to allow them to log in without needing to create a new account.</p>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Generate Token</button>
                    </div>
                </form>
            </div>
        </div>
    </div>