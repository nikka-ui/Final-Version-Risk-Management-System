<script>
(function () {
  const revisionBlocked = @json((bool) ($revisionBlocked ?? false));
  const submitVerb = @json(($isRevise ?? false) ? 'Resubmit ticket' : 'Submit ticket');
  const confirmBox = document.getElementById('confirmBox');
  const submitBtn = document.getElementById('submitBtn');
  const submitBtnNative = document.getElementById('submitBtnNative');
  const submitForm = document.getElementById('submitForm');
  const reviewSection = document.getElementById('reviewSubmissionSection');
  const reviewConfirmBox = document.getElementById('reviewConfirmBox');
  const reviewHint = document.getElementById('reviewConfirmHint');

  function update() {
    if (!confirmBox || !submitBtn) return;
    const checked = confirmBox.checked && !revisionBlocked;
    submitBtn.classList.toggle('btn-enterprise-primary--inactive', !checked);
    submitBtn.classList.toggle('btn-enterprise-next-ready', checked);
    submitBtn.disabled = revisionBlocked;
    reviewSection?.classList.toggle('review-submission-section--confirmed', checked);
    if (reviewHint) {
      reviewHint.textContent = revisionBlocked
        ? 'Update the returned report before submitting.'
        : checked ? 'Confirmed — click "' + submitVerb + '" to send it now.' : 'Required — check this box to enable ' + submitVerb + '.';
    }
  }

  submitBtn?.addEventListener('click', function () {
    if (revisionBlocked) return;
    if (!confirmBox.checked) return;
    submitBtnNative.click();
  });
  submitForm?.addEventListener('submit', function (e) {
    if (revisionBlocked) { e.preventDefault(); return; }
    if (e.submitter?.id === 'submitBtnNative' && !confirmBox.checked) e.preventDefault();
  });
  confirmBox?.addEventListener('change', update);
  confirmBox?.addEventListener('input', update);
  update();
})();
</script>
