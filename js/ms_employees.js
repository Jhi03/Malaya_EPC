// Sample data for demonstration

// Document ready event
document.addEventListener('DOMContentLoaded', function() {
    // Initialize the employee table
    populateEmployeeTable(employees);
    
    // Add event listeners
    setupEventListeners();
});

// Populate the employee table with data
function populateEmployeeTable(data) {
    const tableBody = document.querySelector('table tbody');
    tableBody.innerHTML = '';
    
    if (data.length === 0) {
        tableBody.innerHTML = `<tr><td colspan="8" class="text-center">No employees found</td></tr>`;
        return;
    }
    
    data.forEach(employee => {
        // Determine status badge class
        let statusClass = '';
        switch(employee.status) {
            case 'Full-time':
                statusClass = 'bg-primary';
                break;
            case 'Part-time':
                statusClass = 'bg-info';
                break;
            case 'Contract':
                statusClass = 'bg-success';
                break;
            case 'Pending':
                statusClass = 'bg-warning';
                break;
            default:
                statusClass = 'bg-secondary';
        }
        
        // Create table row
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${employee.name}</td>
            <td>${employee.position}</td>
            <td>${employee.office}</td>
            <td>${employee.age}</td>
            <td>${employee.startDate}</td>
            <td>${employee.salary}</td>
            <td><span class="badge ${statusClass}">${employee.status}</span></td>
            <td>
                <div class="dropdown">
                    <button class="btn btn-sm btn-icon" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-ellipsis-v"></i>
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#editEmployeeModal" data-id="${employee.id}">
                            <i class="fas fa-edit me-2"></i>Edit
                        </a></li>
                        <li><a class="dropdown-item text-danger" href="#" data-id="${employee.id}">
                            <i class="fas fa-trash-alt me-2"></i>Delete
                        </a></li>
                    </ul>
                </div>
            </td>
        `;
        tableBody.appendChild(row);
    });
}

// Set up event listeners for form interactions
function setupEventListeners() {
    // Search functionality
    const searchInput = document.getElementById('searchInput');
    const searchButton = document.getElementById('searchButton');
    
    if (searchButton) {
        searchButton.addEventListener('click', function() {
            performSearch(searchInput.value);
        });
    }
    
    if (searchInput) {
        searchInput.addEventListener('keypress', function(event) {
            if (event.key === 'Enter') {
                performSearch(searchInput.value);
            }
        });
    }
    
    // Entries per page
    const entriesPerPage = document.getElementById('entriesPerPage');
    if (entriesPerPage) {
        entriesPerPage.addEventListener('change', function() {
            // Update the table with the new entries per page value
            // This would typically trigger a server request in a real app
            console.log(`Showing ${entriesPerPage.value} entries per page`);
        });
    }
    
    // Edit employee modal
    const editEmployeeModal = document.getElementById('editEmployeeModal');
    if (editEmployeeModal) {
        editEmployeeModal.addEventListener('show.bs.modal', function(event) {
            // Button that triggered the modal
            const button = event.relatedTarget;
            
            // Extract employee ID from the button's data attribute
            const employeeId = button.getAttribute('data-id');
            
            // Find the employee data
            const employee = employees.find(emp => emp.id == employeeId);
            
            if (employee) {
                // Populate the form fields
                document.getElementById('editEmployeeId').value = employee.id;
                document.getElementById('editFullName').value = employee.name;
                document.getElementById('editPosition').value = employee.position;
                document.getElementById('editOffice').value = employee.office;
                document.getElementById('editSalary').value = employee.salary.replace('$', '').replace(',', '');
                document.getElementById('editStatus').value = employee.status;
                
                // Department would need to be set based on employee data
                // This is just a placeholder as departments aren't in the sample data
                document.getElementById('editDepartment').value = 'System Development & Innovation Lab';
            }
        });
    }
    
    // Add employee form submission
    const addEmployeeForm = document.getElementById('addEmployeeForm');
    if (addEmployeeForm) {
        const addEmployeeButton = document.querySelector('button[form="addEmployeeForm"]');
        if (addEmployeeButton) {
            addEmployeeButton.addEventListener('click', function() {
                // In a real application, this would submit the form data to the