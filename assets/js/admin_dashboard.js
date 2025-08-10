// Admin Dashboard interactions
document.addEventListener('DOMContentLoaded', function() {
  document.querySelectorAll('.dropdown-toggle').forEach(toggle => {
    toggle.addEventListener('click', function(e) {
      e.preventDefault();
      const dropdown = this.parentElement;
      dropdown.classList.toggle('open');
    });
  });

  // Optional: auto-refresh, comment out if not desired
  setInterval(function() {
    if (document.visibilityState === 'visible') {
      location.reload();
    }
  }, 30000);
});

// admin direct agents
document.addEventListener('DOMContentLoaded', () => {
  const agentList = document.getElementById('agentList');
  const agentModal = document.getElementById('agentModal');
  const modalBody = document.getElementById('modalBody');
  const modalCloseBtns = agentModal.querySelectorAll('.close, .cancel-btn');
  const sortSelect = document.getElementById('sort');

  agentList.addEventListener('click', e => {
    if (e.target.matches('.btn-view')) {
      const btn = e.target;

      function docLink(label, path) {
        return path ? `<div class="detail-row"><div class="detail-label">${label}:</div><div class="detail-value"><a href="${path}" target="_blank" class="document-link">View Document</a></div></div>` : '';
      }

      // Additional documents may have multiple comma-separated links
      let additionalDocsHtml = '';
      if (btn.dataset.additionalDocsPath) {
        const docs = btn.dataset.additionalDocsPath.split(',').map(d => d.trim()).filter(d => d);
        additionalDocsHtml = docs.map((doc, i) => 
          `<div class="detail-row"><div class="detail-label">Additional Document ${i+1}:</div><div class="detail-value"><a href="${doc}" target="_blank" class="document-link">View Document</a></div></div>`
        ).join('');
      }

      modalBody.innerHTML = `
        <h3>Personal Information</h3>
        <div class="detail-row"><div class="detail-label">Full Name:</div><div class="detail-value">${btn.dataset.firstName} ${btn.dataset.lastName}</div></div>
        <div class="detail-row"><div class="detail-label">Email:</div><div class="detail-value">${btn.dataset.email}</div></div>
        <div class="detail-row"><div class="detail-label">Phone:</div><div class="detail-value">${btn.dataset.phone || 'N/A'}</div></div>
        <div class="detail-row"><div class="detail-label">Address:</div><div class="detail-value">${btn.dataset.address || 'N/A'}</div></div>

        <h3>Agent Information</h3>
        <div class="detail-row"><div class="detail-label">Agent Type:</div><div class="detail-value">${btn.dataset.userType || 'DIRECT AGENT'}</div></div>
        <div class="detail-row"><div class="detail-label">Broker ID:</div><div class="detail-value">${btn.dataset.brokerId || 'N/A'}</div></div>
        <div class="detail-row"><div class="detail-label">License Number:</div><div class="detail-value">${btn.dataset.licenseNumber || 'N/A'}</div></div>
        <div class="detail-row"><div class="detail-label">Experience:</div><div class="detail-value">${btn.dataset.experienceYears || 'N/A'} years</div></div>
        <div class="detail-row"><div class="detail-label">Specialization:</div><div class="detail-value">${btn.dataset.specialization || 'N/A'}</div></div>
        <div class="detail-row"><div class="detail-label">Company:</div><div class="detail-value">${btn.dataset.companyName || 'N/A'}</div></div>

        <h3>Education & Qualifications</h3>
        <div class="detail-row"><div class="detail-label">Education:</div><div class="detail-value">${btn.dataset.education || 'N/A'}</div></div>
        <div class="detail-row"><div class="detail-label">School:</div><div class="detail-value">${btn.dataset.school || 'N/A'}</div></div>
        <div class="detail-row"><div class="detail-label">Course:</div><div class="detail-value">${btn.dataset.course || 'N/A'}</div></div>
        <div class="detail-row"><div class="detail-label">Graduation Year:</div><div class="detail-value">${btn.dataset.graduationYear || 'N/A'}</div></div>
        <div class="detail-row"><div class="detail-label">Certifications:</div><div class="detail-value">${btn.dataset.certifications || 'N/A'}</div></div>
        <div class="detail-row"><div class="detail-label">Training:</div><div class="detail-value">${btn.dataset.training || 'N/A'}</div></div>

        <h3>Uploaded Documents</h3>
        ${docLink('Broker License', btn.dataset.brokerLicensePath)}
        ${docLink('PRC License', btn.dataset.prcLicensePath)}
        ${docLink('Resume/CV', btn.dataset.resumePath)}
        ${docLink('Valid ID', btn.dataset.validIdPath)}
        ${additionalDocsHtml || '<div class="detail-row"><div class="detail-value">No additional documents uploaded</div></div>'}

        <h3>Account Information</h3>
        <div class="detail-row"><div class="detail-label">Account Created:</div><div class="detail-value">${new Date(btn.dataset.accountCreated).toLocaleDateString()}</div></div>
        <div class="detail-row"><div class="detail-label">Status:</div><div class="detail-value">${btn.dataset.status || 'N/A'}</div></div>
      `;

      agentModal.style.display = 'block';
    } else if (e.target.matches('.btn-remove')) {
      alert('Remove agent functionality not implemented yet.');
    }
  });

  modalCloseBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      agentModal.style.display = 'none';
    });
  });

  window.addEventListener('click', e => {
    if (e.target === agentModal) {
      agentModal.style.display = 'none';
    }
  });

  sortSelect.addEventListener('change', () => {
    const sortBy = sortSelect.value;
    const cards = Array.from(agentList.querySelectorAll('.direct-agent-card'));
    let sorted;
    switch (sortBy) {
      case 'date':
        sorted = cards.sort((a, b) => new Date(b.dataset.date) - new Date(a.dataset.date));
        break;
      case 'name':
        sorted = cards.sort((a, b) => a.dataset.name.localeCompare(b.dataset.name));
        break;
      case 'experience':
        sorted = cards.sort((a, b) => parseInt(b.dataset.experience) - parseInt(a.dataset.experience));
        break;
    }
    sorted.forEach(card => agentList.appendChild(card));
  });
});

//admin application functions
document.addEventListener('DOMContentLoaded', () => {
  const applicationList = document.getElementById('applicationList');
  const modal = document.getElementById('applicationModal');
  const modalBody = document.getElementById('modalBody');

  // Event delegation for view / approve / reject
  applicationList.addEventListener('click', (e) => {
    const viewBtn = e.target.closest('.view-btn');
    const approveBtn = e.target.closest('.approve-btn');
    const rejectBtn = e.target.closest('.reject-btn');

    if (viewBtn) {
      const id = viewBtn.dataset.id;
      fetchApplicationDetails(id);
    } else if (approveBtn) {
      const id = approveBtn.dataset.id;
      reviewApplication(id, 'approve');
    } else if (rejectBtn) {
      const id = rejectBtn.dataset.id;
      reviewApplication(id, 'reject');
    }
  });

  // Sort
  const sortEl = document.getElementById('sort');
  if (sortEl) {
    sortEl.addEventListener('change', () => {
      const sortBy = sortEl.value;
      const cards = Array.from(document.querySelectorAll('.application-card'));
      let sorted;
      switch (sortBy) {
        case 'newest':
          sorted = cards.sort((a,b)=> new Date(b.dataset.date) - new Date(a.dataset.date));
          break;
        case 'oldest':
          sorted = cards.sort((a,b)=> new Date(a.dataset.date) - new Date(b.dataset.date));
          break;
        case 'name':
          sorted = cards.sort((a,b)=> a.dataset.name.localeCompare(b.dataset.name));
          break;
        case 'type':
          sorted = cards.sort((a,b)=> a.dataset.type.localeCompare(b.dataset.type));
          break;
        default: sorted = cards;
      }
      const container = document.getElementById('applicationList');
      sorted.forEach(c => container.appendChild(c));
    });
  }

  // Modal close handlers
  document.querySelectorAll('.modal .close, .modal .cancel-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      modal.style.display = 'none';
    });
  });
  window.addEventListener('click', (ev) => {
    if (ev.target === modal) modal.style.display = 'none';
  });

  // Fetch and show application details (expects JSON from get_application_details.php?id=)
  function fetchApplicationDetails(id) {
    fetch('get_application_details.php?id=' + encodeURIComponent(id))
      .then(res => res.json())
      .then(data => {
        if (!data.success) {
          alert('Failed to load application details');
          return;
        }
        const app = data.application;
        // build modal HTML (keep minimal; match your detail-section markup)
        modalBody.innerHTML = `
          <div class="detail-section">
            <h3>Personal Information</h3>
            <div class="detail-row"><div class="detail-label">Full Name:</div><div class="detail-value">${escapeHtml(app.first_name)} ${escapeHtml(app.last_name)}</div></div>
            <div class="detail-row"><div class="detail-label">Email:</div><div class="detail-value">${escapeHtml(app.email)}</div></div>
            <div class="detail-row"><div class="detail-label">Phone:</div><div class="detail-value">${escapeHtml(app.phone || 'N/A')}</div></div>
            <div class="detail-row"><div class="detail-label">Address:</div><div class="detail-value">${escapeHtml(app.address || 'N/A')}</div></div>
          </div>
          <div class="detail-section">
            <h3>Agent Info</h3>
            <div class="detail-row"><div class="detail-label">Type:</div><div class="detail-value">${escapeHtml((app.agent_type || '').replace('_',' ')).toUpperCase()}</div></div>
            <div class="detail-row"><div class="detail-label">Broker ID:</div><div class="detail-value">${escapeHtml(app.broker_id || 'N/A')}</div></div>
            <div class="detail-row"><div class="detail-label">Experience:</div><div class="detail-value">${escapeHtml(app.experience_years || 'N/A')} years</div></div>
            <div class="detail-row"><div class="detail-label">Company:</div><div class="detail-value">${escapeHtml(app.company_name || 'N/A')}</div></div>
          </div>
        `;
        modal.style.display = 'flex';
      })
      .catch(err => {
        console.error(err);
        alert('Error loading details');
      });
  }

  function reviewApplication(id, action) {
    if (!confirm(`Are you sure you want to ${action} this application?`)) return;
    const form = new FormData();
    form.append('application_id', id);
    form.append('action', action);
    form.append('admin_notes', '');

    // NOTE: use the path that exists in your app: update_applicant.php vs update_application.php
    fetch('update_applicant.php', {
      method: 'POST',
      body: form
    })
    .then(res => {
      // server usually redirects; consider checking response.ok
      if (res.ok) window.location.reload();
      else alert('Error updating application');
    })
    .catch(err => {
      console.error(err);
      alert('Error updating application');
    });
  }

  // small helper to avoid XSS when inserting text
  function escapeHtml(str) {
    if (!str) return '';
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
    } 
  });

