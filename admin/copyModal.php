<div class="modal fade" id="copyModal" tabindex="-1" role="dialog" aria-labelledby="copyModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="copyModalLabel">Copy User</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="copyForm">
                    <div class="modal-body">
                        <input type="hidden" id="copyUserId" name="id">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="copyEmail">Email</label>
                                    <input type="email" class="form-control" id="copyEmail" name="email" readonly>
                                </div>
                                <div class="form-group">
                                    <label for="copyName">Name</label>
                                    <input type="text" class="form-control" id="copyName" name="name" readonly>
                                </div>
                                <div class="form-group">
                                    <label for="copyActive">Active</label>
                                    <select class="form-control" id="copyActive" name="active">
                                        <option value="1">Yes</option>
                                        <option value="0">No</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="copyIsAdmin">Admin</label>
                                    <select class="form-control" id="copyIsAdmin" name="is_admin">
                                        <option value="1">Yes</option>
                                        <option value="0">No</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="copyDisplayName">Display Name</label>
                                    <input type="text" class="form-control" id="copyDisplayName" name="display_name" readonly>
                                </div>
                                <div class="form-group">
                                    <label for="copyComputerId">Computer ID</label>
                                    <input type="text" class="form-control" id="copyComputerId" name="Computer_ID" readonly>
                                    <small class="form-text text-muted">This will be copied but should be changed later for the new user.</small>
                                </div>
                                <div class="form-group">
                                    <label for="copyAffiliate">Affiliate</label>
                                    <select class="form-control" id="copyAffiliate" name="affiliate">
                                        <option value="0">No</option>
                                        <option value="1">Yes</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="copyAffiliateCode">Affiliate Code</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="copyAffiliateCode" name="affiliate_code">
                                        <div class="input-group-append">
                                            <button type="button" class="btn btn-info generate-code-btn" onclick="generateAffiliateCodeForField('#copyAffiliateCode')">Generate</button>
                                            <button type="button" class="btn btn-success copy-link-btn">Copy Link</button>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="copyReferralBy">Referral By</label>
                                    <input type="text" class="form-control" id="copyReferralBy" name="referral_by" readonly>
                                </div>
                            </div>
                        </div>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> This will create a new user with a unique ID based on the original user, but with the same data. You can adjust affiliate settings before copying.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Copy User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>