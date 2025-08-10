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


//admin direct agent functions
document.addEventListener('DOMContentLoaded', () => {
  const agentList = document.getElementById('agentList');
  const agentModal = document.getElementById('agentModal');
  const modalBody = document.getElementById('modalBody');
  const modalCloseBtns = agentModal.querySelectorAll('.close, .cancel-btn');
  const sortSelect = document.getElementById('sort');

  // View Details handler
  agentList.addEventListener('click', e => {
    if (e.target.matches('.btn-view')) {
      const agentId = e.target.getAttribute('data-agent-id');
      fetchAgentDetails(agentId);
    } else if (e.target.matches('.btn-remove')) {
      const agentId = e.target.getAttribute('data-agent-id');
      removeAgent(agentId);
    }
  });

  // Close modal
  modalCloseBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      agentModal.style.display = 'none';
    });
  });

  // Close modal on outside click
  window.addEventListener('click', e => {
    if (e.target === agentModal) {
      agentModal.style.display = 'none';
    }
  });

  // Sort handler
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

  // Fetch and show agent details in modal
  function fetchAgentDetails(agentId) {
    fetch(`get_agent_details.php?id=${agentId}`)
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          const agent = data.agent;
          modalBody.innerHTML = `
            <div class="detail-section">
              <h3>Personal Information</h3>
              <div class="detail-row"><div class="detail-label">Full Name:</div><div class="detail-value">${agent.first_name} ${agent.last_name}</div></div>
              <div class="detail-row"><div class="detail-label">Email:</div><div class="detail-value">${agent.email}</div></div>
              <div class="detail-row"><div class="detail-label">Phone:</div><div class="detail-value">${agent.phone || 'N/A'}</div></div>
              <div class="detail-row"><div class="detail-label">Address:</div><div class="detail-value">${agent.address || 'N/A'}</div></div>
            </div>
            <div class="detail-section">
              <h3>Agent Information</h3>
              <div class="detail-row"><div class="detail-label">Agent Type:</div><div class="detail-value">${(agent.user_type || 'direct_agent').replace('_', ' ').toUpperCase()}</div></div>
              <div class="detail-row"><div class="detail-label">Broker ID:</div><div class="detail-value">${agent.broker_id || 'N/A'}</div></div>
              <div class="detail-row"><div class="detail-label">License Number:</div><div class="detail-value">${agent.license_number || 'N/A'}</div></div>
              <div class="detail-row"><div class="detail-label">Experience:</div><div class="detail-value">${agent.experience_years || 'N/A'} years</div></div>
              <div class="detail-row"><div class="detail-label">Specialization:</div><div class="detail-value">${agent.specialization || 'N/A'}</div></div>
              <div class="detail-row"><div class="detail-label">Company:</div><div class="detail-value">${agent.company_name || 'N/A'}</div></div>
            </div>
            <div class="detail-section">
              <h3>Education & Qualifications</h3>
              <div class="detail-row"><div class="detail-label">Education:</div><div class="detail-value">${agent.education || 'N/A'}</div></div>
              <div class="detail-row"><div class="detail-label">School:</div><div class="detail-value">${agent.school || 'N/A'}</div></div>
              <div class="detail-row"><div class="detail-label">Course:</div><div class="detail-value">${agent.course || 'N/A'}</div></div>
              <div class="detail-row"><div class="detail-label">Graduation Year:</div><div class="detail-value">${agent.graduation_year || 'N/A'}</div></div>
              <div class="detail-row"><div class="detail-label">Certifications:</div><div class="detail-value">${agent.certifications || 'N/A'}</div></div>
              <div class="detail-row"><div class="detail-label">Training:</div><div class="detail-value">${agent.training || 'N/A'}</div></div>
            </div>
            <div class="detail-section">
              <h3>Uploaded Documents</h3>
              ${agent.broker_license_path ? `<div class="detail-row"><div class="detail-label">Broker License:</div><div class="detail-value"><a href="${agent.broker_license_path}" target="_blank" class="document-link">View Document</a></div></div>` : ''}
              ${agent.prc_license_path ? `<div class="detail-row"><div class="detail-label">PRC License:</div><div class="detail-value"><a href="${agent.prc_license_path}" target="_blank" class="document-link">View Document</a></div></div>` : ''}
              ${agent.resume_path ? `<div class="detail-row"><div class="detail-label">Resume/CV:</div><div class="detail-value"><a href="${agent.resume_path}" target="_blank" class="document-link">View Document</a></div></div>` : ''}
              ${agent.valid_id_path ? `<div class="detail-row"><div class="detail-label">Valid ID:</div><div class="detail-value"><a href="${agent.valid_id_path}" target="_blank" class="document-link">View Document</a></div></div>` : ''}
              ${agent.additional_docs_path ? `<div class="detail-row"><div class="detail-label">Additional Documents:</div><div class="detail-value">${agent.additional_docs_path.split(',').map(doc => `<a href="${doc.trim()}" target="_blank" class="document-link">View Document</a>`).join('<br>')}</div></div>` : ''}
              ${!agent.broker_license_path && !agent.prc_license_path && !agent.resume_path && !agent.valid_id_path && !agent.additional_docs_path ? '<div class="detail-row"><div class="detail-value">No documents uploaded</div></div>' : ''}
            </div>
            <div class="detail-section">
              <h3>Account Information</h3>
              <div class="detail-row"><div class="detail-label">Account Created:</div><div class="detail-value">${new Date(agent.created_at).toLocaleDateString()}</div></div>
              <div class="detail-row"><div class="detail-label">Status:</div><div class="detail-value">${(agent.status || 'active').toUpperCase()}</div></div>
            </div>
          `;

          agentModal.style.display = 'block';
        } else {
          alert('Error loading agent details');
        }
      })
      .catch(err => {
        console.error('Fetch error:', err);
        alert('Error loading agent details');
      });
  }

  // Remove agent placeholder
  function removeAgent(agentId) {
    if (confirm('Are you sure you want to remove this agent? This action cannot be undone.')) {
      // Implement your remove logic here, e.g., AJAX call to delete agent
      alert('Remove agent functionality is not implemented yet.');
    }
  }
});


//admin performance functions
