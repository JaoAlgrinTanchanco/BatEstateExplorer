// Admin Dashboard interactions
document.addEventListener('DOMContentLoaded', function() {
  document.querySelectorAll('.dropdown-toggle').forEach(toggle => {
    toggle.addEventListener('click', function(e) {
      e.preventDefault();
      const dropdown = this.parentElement;
      dropdown.classList.toggle('open');
    });
  });


});

document.addEventListener("DOMContentLoaded", () => {
  const sidebarWrapper = document.querySelector(".sidebar-wrapper");
  const toggleBtn = document.querySelector(".sidebar-toggle");

  toggleBtn.addEventListener("click", () => {
    sidebarWrapper.classList.toggle("collapsed");
  });
});


// admin direct agents
// admin direct agents
document.addEventListener('DOMContentLoaded', () => {
  const agentList = document.getElementById('agentList');
  const agentModal = document.getElementById('agentModal');
  const modalBody = document.getElementById('modalBody');
  const modalCloseBtns = agentModal ? agentModal.querySelectorAll('.close, .cancel-btn') : [];
  const sortSelect = document.getElementById('sort');

  // Close modal buttons
  modalCloseBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      if (agentModal) agentModal.style.display = 'none';
    });
  });

  // Only attach event listener if agentList exists
  if (agentList) {
    agentList.addEventListener('click', async (e) => {
      if (e.target.matches('.btn-view')) {
        const btn = e.target;

        function docLink(label, path) {
          return path
            ? `<div class="detail-row"><div class="detail-label">${label}:</div>
              <div class="detail-value"><a href="${path}" target="_blank" class="document-link">View Document</a></div></div>`
            : '';
        }

        let additionalDocsHtml = '';
        if (btn.dataset.additionalDocsPath) {
          const docs = btn.dataset.additionalDocsPath
            .split(',')
            .map(d => d.trim())
            .filter(d => d);
          additionalDocsHtml = docs.map((doc, i) =>
            `<div class="detail-row"><div class="detail-label">Additional Document ${i + 1}:</div>
            <div class="detail-value"><a href="${doc}" target="_blank" class="document-link">View Document</a></div></div>`
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
      }

      else if (e.target.matches('.btn-remove')) {
        const agentId = e.target.dataset.agentId;
        if (!agentId) return;

        if (!confirm('Are you sure you want to remove this agent account? This action cannot be undone.')) return;

        try {
          const response = await fetch('/BatEstateExplorer/database/remove_agent.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ agentId })
          });

          const data = await response.json();

          if (response.ok && data.success) {
            const card = e.target.closest('.direct-agent-card');
            if (card) card.remove();
            alert('Agent account removed successfully.');
          } else {
            alert('Failed to remove agent: ' + (data.error || 'Unknown error'));
          }
        } catch (error) {
          alert('Error removing agent: ' + error.message);
        }
      }
    });

    // Sorting
    if (sortSelect) {
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
    }
  }

  // Close modal if clicking outside
  window.addEventListener('click', e => {
    if (e.target === agentModal) {
      agentModal.style.display = 'none';
    }
  });
});

//admin application functions
