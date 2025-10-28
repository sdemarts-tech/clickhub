<?php $pageTitle = 'Admin'; include __DIR__.'/partials/header.php'; ?>
  <section class="card">
    <h1 class="page-title">Referral Review</h1>

    <div class="tabs" id="admin-tabs">
      <button class="tab tab--active" data-tab="pending">Pending</button>
      <button class="tab" data-tab="approved">Approved</button>
      <button class="tab" data-tab="rejected">Rejected</button>
    </div>

    <div class="tabpanels">
      <div class="tabpanel tabpanel--active" id="tab-pending">
        <table class="table" id="table-pending">
          <thead><tr><th>Date</th><th>Referrer</th><th>Referee</th><th>Notes</th><th>Action</th></tr></thead>
          <tbody></tbody>
        </table>
      </div>

      <div class="tabpanel" id="tab-approved">
        <table class="table" id="table-approved">
          <thead><tr><th>Date</th><th>Referrer</th><th>Referee</th><th>Notes</th></tr></thead>
          <tbody></tbody>
        </table>
      </div>

      <div class="tabpanel" id="tab-rejected">
        <table class="table" id="table-rejected">
          <thead><tr><th>Date</th><th>Referrer</th><th>Referee</th><th>Notes</th></tr></thead>
          <tbody></tbody>
        </table>
      </div>
    </div>
  </section>
<?php include __DIR__.'/partials/footer.php'; ?>
