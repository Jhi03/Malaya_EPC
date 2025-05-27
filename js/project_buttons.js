// Save this as project_buttons.js
(function() {
    // Ensure Logger exists
    if (typeof window.Logger === 'undefined') {
        window.Logger = {
            log: console.log,
            error: console.error,
            warn: console.warn,
            info: console.info
        };
    }

    // Wait for DOM to be fully loaded
    document.addEventListener('DOMContentLoaded', function() {
        console.log("=== PROJECT BUTTONS SCRIPT LOADED ===");
        
        // Get project ID from URL or hidden field
        const projectId = document.querySelector('input[name="projectId"]')?.value || 
                        new URLSearchParams(window.location.search).get('projectId');
        
        console.log("Current project ID:", projectId);
        console.log("Is corporate view:", projectId == 1);
        
        // Detect buttons on page
        const addBtn = document.querySelector('.add-record-btn') || document.getElementById('add-record-btn');
        const editBtns = document.querySelectorAll('.edit-btn');
        const deleteBtns = document.querySelectorAll('.delete-btn');
        const viewBtns = document.querySelectorAll('.view-btn');
        const ellipsisBtns = document.querySelectorAll('.ellipsis-btn');
        
        console.log("Button detection on page load:");
        console.log("- Add button found:", !!addBtn);
        console.log("- Edit buttons found:", editBtns.length);
        console.log("- Delete buttons found:", deleteBtns.length);
        console.log("- View buttons found:", viewBtns.length);
        console.log("- Ellipsis buttons found:", ellipsisBtns.length);
        
        // Global error handler
        window.onerror = function(message, source, lineno, colno, error) {
            console.error("JavaScript error:", message, "at", source, ":", lineno);
            return false;
        };
        
        // ======= CORE FUNCTIONS =======
        
        // Open expense modal function
        function openExpenseModal(mode, recordData = null) {
            console.log("Opening expense modal:", mode, recordData);
            
            const modal = document.getElementById('expenseModal');
            if (!modal) {
                console.error("Expense modal not found!");
                return;
            }
            
            const modalTitle = document.getElementById('expenseModalLabel');
            const formMode = document.getElementById('form_mode');
            const editId = document.getElementById('edit_id');
            
            // Reset form
            document.getElementById('expenseForm').reset();
            
            // Reset all inputs to default state
            document.getElementById('expense_input').style.display = 'block';
            document.getElementById('rental_input').style.display = 'none';
            document.getElementById('tax_input').style.display = 'block';
            document.getElementById('invoice_input').style.display = 'block';
            document.getElementById('tax').disabled = true;
            document.getElementById('invoice_no').disabled = true;
            document.getElementById('budget').disabled = true;
            
            if (document.getElementById('bill_to_client_checkbox')) {
                document.getElementById('bill_to_client_checkbox').disabled = false;
            }
            
            if (document.getElementById('tax_edit_btn')) {
                document.getElementById('tax_edit_btn').style.display = 'none';
                document.getElementById('tax_edit_icon').src = 'icons/pencil-black.svg';
                document.getElementById('tax_edit_btn').classList.remove('btn-primary');
                document.getElementById('tax_edit_btn').classList.add('btn-outline-secondary');
            }
            
            // Reset summary display
            document.getElementById('summary_expense_row').style.display = 'flex';
            document.getElementById('summary_rental_row').style.display = 'none';
            document.getElementById('summary_tax_row').style.display = 'none';
            document.getElementById('summary_budget_row').style.display = 'none';
            document.getElementById('summary_variance_row').style.display = 'none';
            document.getElementById('summary_bill_row').style.display = 'none';
            document.getElementById('summary_loss_row').style.display = 'none';
            
            if (mode === 'add') {
                modalTitle.textContent = 'Add New Expense Record';
                formMode.value = 'add';
                editId.value = '0';
                document.getElementById('purchase_date').value = new Date().toISOString().split('T')[0]; // Today's date
            } else if (mode === 'edit' && recordData) {
                modalTitle.textContent = 'Edit Expense Record';
                formMode.value = 'edit';
                editId.value = recordData.id;
                
                // Fill the form with existing data
                document.getElementById('category').value = recordData.category;
                
                // Force a synchronous change event on the category dropdown
                const categoryEvent = new Event('change', { bubbles: true });
                document.getElementById('category').dispatchEvent(categoryEvent);
                
                // Wait a bit longer to ensure subcategories are loaded before selecting
                setTimeout(() => {
                    const subcategorySelect = document.getElementById('subcategory');
                    if (subcategorySelect) {
                        subcategorySelect.value = recordData.subcategory || '';
                        
                        // If subcategory isn't found in the dropdown, try to add it
                        if (subcategorySelect.value !== recordData.subcategory && recordData.subcategory) {
                            const option = document.createElement('option');
                            option.value = recordData.subcategory;
                            option.textContent = recordData.subcategory;
                            subcategorySelect.appendChild(option);
                            subcategorySelect.value = recordData.subcategory;
                        }
                    }
                }, 300);
                
                document.getElementById('purchase_date').value = recordData.date;
                document.getElementById('payee').value = recordData.payee;
                document.getElementById('record_description').value = recordData.record_description;
                document.getElementById('remarks').value = recordData.remarks;
                
                // Check if is_rental is "Yes"
                if (recordData.is_rental === 'Yes' || 
                    (recordData.rental_rate && parseFloat(recordData.rental_rate) > 0)) {
                    document.getElementById('is_rental').checked = true;
                    document.getElementById('is_rental_field').value = 'Yes';
                    document.getElementById('rental_rate').value = recordData.rental_rate;
                    document.getElementById('expense_input').style.display = 'none';
                    document.getElementById('rental_input').style.display = 'block';
                    document.getElementById('summary_expense_row').style.display = 'none';
                    document.getElementById('summary_rental_row').style.display = 'flex';
                    document.getElementById('rental_rate').required = true;
                    document.getElementById('expense').required = false;
                } else {
                    document.getElementById('expense').value = recordData.expense;
                    document.getElementById('is_rental_field').value = 'No';
                }
                
                // Check if budget exists and is not zero
                if (recordData.budget && parseFloat(recordData.budget) > 0) {
                    document.getElementById('has_budget').checked = true;
                    document.getElementById('has_budget_field').value = 'on';
                    document.getElementById('budget').disabled = false;
                    document.getElementById('budget').required = true;
                    document.getElementById('budget').value = recordData.budget;
                    document.getElementById('summary_budget_row').style.display = 'flex';
                    document.getElementById('summary_variance_row').style.display = 'flex';
                    
                    // Only disable Bill to Client if budget >= expense/rental_rate
                    const expenseAmount = parseFloat(recordData.is_rental === 'Yes' ? recordData.rental_rate : recordData.expense) || 0;
                    const budgetAmount = parseFloat(recordData.budget) || 0;
                    
                    if (document.getElementById('bill_to_client_checkbox')) {
                        if (expenseAmount <= budgetAmount) {
                            document.getElementById('bill_to_client_checkbox').disabled = true;
                        } else {
                            document.getElementById('bill_to_client_checkbox').disabled = false;
                        }
                    }
                }
                
                // Check if bill_to_client is "Yes"
                if (recordData.bill_to_client === 'Yes' && document.getElementById('bill_to_client_checkbox')) {
                    document.getElementById('bill_to_client_checkbox').checked = true;
                    document.getElementById('bill_to_client').value = 'Yes';
                    document.getElementById('summary_bill').textContent = 'Yes';
                    document.getElementById('summary_bill_row').style.display = 'flex';
                }
                
                // Check if tax exists and is not zero
                if (recordData.tax && parseFloat(recordData.tax) > 0) {
                    document.getElementById('has_tax').checked = true;
                    document.getElementById('tax').value = recordData.tax;
                    document.getElementById('tax_edit_btn').style.display = 'block';
                    document.getElementById('summary_tax_row').style.display = 'flex';
                }
                
                // Check if invoice exists
                if (recordData.invoice_no && recordData.invoice_no.trim() !== '') {
                    document.getElementById('has_invoice').checked = true;
                    document.getElementById('invoice_no').disabled = false;
                    document.getElementById('invoice_no').required = true;
                    document.getElementById('invoice_no').value = recordData.invoice_no;
                }
                
                // Update calculations
                updateCalculations();
                
                // Update company loss status
                updateCompanyLossStatus();
            }
            
            // Show the modal using Bootstrap if available
            if (typeof bootstrap !== 'undefined') {
                try {
                    const bsModal = new bootstrap.Modal(modal);
                    bsModal.show();
                    console.log("Expense modal shown via Bootstrap");
                } catch (error) {
                    console.error("Error showing expense modal:", error);
                    // Fallback
                    modal.style.display = 'block';
                    modal.classList.add('show');
                    document.body.classList.add('modal-open');
                }
            } else {
                // Fallback if Bootstrap is not available
                modal.style.display = 'block';
                modal.classList.add('show');
                document.body.classList.add('modal-open');
            }
        }

        function deleteExpense(id) {
            if (confirm('Are you sure you want to delete this expense record?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.style.display = 'none';
                
                const idInput = document.createElement('input');
                idInput.type = 'hidden';
                idInput.name = 'record_id';
                idInput.value = id;
                
                const deleteInput = document.createElement('input');
                deleteInput.type = 'hidden';
                deleteInput.name = 'delete_record';
                deleteInput.value = '1';
                
                form.appendChild(idInput);
                form.appendChild(deleteInput);
                document.body.appendChild(form);
                
                form.submit();
            }
        }

        function deleteProject(projectId) {
            if (!confirm('Are you sure you want to delete this project?')) {
                return;
            }

            const xhr = new XMLHttpRequest();
            xhr.open('POST', window.location.href, true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

            xhr.onload = function() {
                if (xhr.status === 200) {
                    if (xhr.responseText.trim() === 'success') {
                        alert('Project deleted successfully.');
                        window.location.href = 'ms_projects.php';
                    } else {
                        alert('Failed to delete project: ' + xhr.responseText);
                    }
                } else {
                    alert('Request failed. Returned status of ' + xhr.status);
                }
            };

            xhr.send('delete_project_id=' + encodeURIComponent(projectId));
        }
        
        function toggleDropdown(button) {
            const menu = button.nextElementSibling;
            if (menu && menu.classList.contains('dropdown-menu')) {
                if (menu.style.display === 'block') {
                    menu.style.display = 'none';
                } else {
                    // Close any other open dropdowns first
                    document.querySelectorAll('.dropdown-menu').forEach(m => {
                        m.style.display = 'none';
                    });
                    menu.style.display = 'block';
                }
            }
        }

        // Open edit project modal function
        function openEditProjectModal() {
            console.log("Opening edit project modal");
            
            // Try both possible IDs
            const modal = document.getElementById('editProjectModal') || 
                           document.getElementById('editProjectPanel');
            
            if (!modal) {
                console.error("Edit project modal not found!");
                return;
            }
            
            console.log("Found edit project modal:", modal.id);
            
            // Show the modal using Bootstrap if available
            if (typeof bootstrap !== 'undefined') {
                try {
                    const bsModal = new bootstrap.Modal(modal);
                    bsModal.show();
                    console.log("Edit project modal shown via Bootstrap");
                } catch (error) {
                    console.error("Error showing edit project modal:", error);
                    // Fallback for Bootstrap errors
                    modal.style.display = 'block';
                    modal.classList.add('show');
                    document.body.classList.add('modal-open');
                }
            } else {
                // Fallback if Bootstrap is not available
                modal.style.display = 'block';
                modal.classList.add('show');
                modal.classList.add('open'); // For custom panel implementation
                document.body.classList.add('modal-open');
            }
        }

        // View expense record function
        function openViewExpenseModal(button) {
            console.log("Opening view expense modal");
            
            const modal = document.getElementById('viewExpenseModal');
            if (!modal) {
                console.error("View expense modal not found!");
                return;
            }
            
            // Fill modal fields
            modal.querySelector('#view_category').textContent = button.getAttribute('data-category') || 'N/A';
            modal.querySelector('#view_subcategory').textContent = button.getAttribute('data-subcategory') || 'N/A';
            modal.querySelector('#view_purchase_date').textContent = button.getAttribute('data-date') || 'N/A';
            modal.querySelector('#view_description').textContent = button.getAttribute('data-record_description') || 'N/A';
            modal.querySelector('#view_budget').textContent = '₱' + parseFloat(button.getAttribute('data-budget') || 0).toFixed(2);
            modal.querySelector('#view_expense').textContent = '₱' + parseFloat(button.getAttribute('data-expense') || 0).toFixed(2);
            modal.querySelector('#view_variance').textContent = '₱' + parseFloat(button.getAttribute('data-variance') || 0).toFixed(2);
            modal.querySelector('#view_rental_rate').textContent = '₱' + parseFloat(button.getAttribute('data-rental_rate') || 0).toFixed(2);
            modal.querySelector('#view_tax').textContent = '₱' + parseFloat(button.getAttribute('data-tax') || 0).toFixed(2);
            modal.querySelector('#view_payee').textContent = button.getAttribute('data-payee') || 'N/A';
            modal.querySelector('#view_invoice_no').textContent = button.getAttribute('data-invoice_no') || 'N/A';
            modal.querySelector('#view_remarks').textContent = button.getAttribute('data-remarks') || 'No remarks';
            
            modal.querySelector('#view_bill_to_client').textContent = button.getAttribute('data-bill_to_client') || 'No';
            modal.querySelector('#view_is_rental').textContent = button.getAttribute('data-is_rental') || 'No';
            modal.querySelector('#view_is_company_loss').textContent = button.getAttribute('data-is_company_loss') || 'No';
            
            // Handle conditional rows
            if (button.getAttribute('data-bill_to_client') === 'Yes') {
                modal.querySelector('#view_bill_to_client').closest('tr').style.display = '';
                modal.querySelector('#view_bill_to_client').closest('tr').classList.add('table-success');
            } else {
                modal.querySelector('#view_bill_to_client').closest('tr').style.display = 'none';
            }
            
            if (button.getAttribute('data-is_company_loss') === 'Yes') {
                modal.querySelector('#view_is_company_loss').closest('tr').style.display = '';
                modal.querySelector('#view_is_company_loss').closest('tr').classList.add('table-danger');
            } else {
                modal.querySelector('#view_is_company_loss').closest('tr').style.display = 'none';
            }
            
            if (button.getAttribute('data-is_rental') === 'Yes') {
                modal.querySelector('#view_rental_rate').closest('tr').classList.add('table-primary');
            } else {
                modal.querySelector('#view_rental_rate').closest('tr').classList.remove('table-primary');
            }
            
            // Get additional details via AJAX
            const recordId = button.getAttribute('data-id');
            fetch('get_expense_details.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: 'record_id=' + encodeURIComponent(recordId)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    modal.querySelector('#view_created_by').textContent = data.created_by_name || 'Unknown';
                    modal.querySelector('#view_creation_date').textContent = data.creation_date || 'N/A';
                    modal.querySelector('#view_edited_by').textContent = data.edited_by_name || 'N/A';
                    modal.querySelector('#view_edit_date').textContent = data.edit_date || 'N/A';
                }
            })
            .catch(error => {
                console.error("Error fetching expense details:", error);
            });
            
            // Show the modal using Bootstrap if available
            if (typeof bootstrap !== 'undefined') {
                try {
                    const bsModal = new bootstrap.Modal(modal);
                    bsModal.show();
                    console.log("View modal shown via Bootstrap");
                } catch (error) {
                    console.error("Error showing view modal:", error);
                    // Fallback
                    modal.style.display = 'block';
                    modal.classList.add('show');
                    document.body.classList.add('modal-open');
                }
            } else {
                // Fallback if Bootstrap is not available
                modal.style.display = 'block';
                modal.classList.add('show');
                document.body.classList.add('modal-open');
            }
        }

        // Calculate functions
        function updateCalculations() {
            if (!document.getElementById('has_budget') || !document.getElementById('budget')) return;
            
            const hasBudget = document.getElementById('has_budget').checked;
            const budget = hasBudget ? (parseFloat(document.getElementById('budget').value) || 0) : 0;
            
            const isRental = document.getElementById('is_rental').checked;
            const expense = isRental ? 
                (parseFloat(document.getElementById('rental_rate').value) || 0) : 
                (parseFloat(document.getElementById('expense').value) || 0);
            
            const hasTax = document.getElementById('has_tax').checked;
            const tax = hasTax ? (parseFloat(document.getElementById('tax').value) || 0) : 0;
            
            // Calculate variance only if budget is enabled
            if (hasBudget) {
                const variance = budget - expense;
                document.getElementById('variance').value = variance.toFixed(2);
                document.getElementById('summary_variance').textContent = '₱' + variance.toFixed(2);
            } else {
                document.getElementById('variance').value = '';
                document.getElementById('variance').placeholder = '0.00';
            }
            
            // Update summary display
            if (hasBudget) {
                document.getElementById('summary_budget').textContent = '₱' + budget.toFixed(2);
            }
            
            if (isRental) {
                document.getElementById('summary_rental').textContent = '₱' + expense.toFixed(2);
            } else {
                document.getElementById('summary_expense').textContent = '₱' + expense.toFixed(2);
            }
            
            if (hasTax) {
                document.getElementById('summary_tax').textContent = '₱' + tax.toFixed(2);
            }
        }
        
        function updateCompanyLossStatus() {
            if (!document.getElementById('has_budget') || !document.getElementById('bill_to_client_checkbox')) return;
            
            const hasBudget = document.getElementById('has_budget').checked;
            const isBillToClient = document.getElementById('bill_to_client_checkbox').checked;
            const expenseAmount = document.getElementById('is_rental').checked 
                ? parseFloat(document.getElementById('rental_rate').value || 0) 
                : parseFloat(document.getElementById('expense').value || 0);
            const budgetAmount = parseFloat(document.getElementById('budget').value || 0);
            
            let isCompanyLoss = false;
            
            // Scenario 1: Budget checked, expense <= budget
            if (hasBudget && expenseAmount <= budgetAmount) {
                isCompanyLoss = false;
            }
            // Scenario 2: Budget NOT checked, bill_to_client NOT checked
            else if (!hasBudget && !isBillToClient) {
                isCompanyLoss = true;
            }
            // Scenario 3: Budget checked, expense > budget, bill_to_client NOT checked
            else if (hasBudget && expenseAmount > budgetAmount && !isBillToClient) {
                isCompanyLoss = true;
            }
            // Scenario 4: Budget checked, expense > budget, bill_to_client checked
            else if (hasBudget && expenseAmount > budgetAmount && isBillToClient) {
                isCompanyLoss = false;
            }
            
            // Add a hidden field for is_company_loss if it doesn't exist
            let isCompanyLossField = document.getElementById('is_company_loss');
            if (!isCompanyLossField) {
                isCompanyLossField = document.createElement('input');
                isCompanyLossField.type = 'hidden';
                isCompanyLossField.id = 'is_company_loss';
                isCompanyLossField.name = 'is_company_loss';
                document.getElementById('expenseForm').appendChild(isCompanyLossField);
            }
            
            // Update the value
            isCompanyLossField.value = isCompanyLoss ? 'Yes' : 'No';
            
            // Add a row to the expense summary
            const summaryLossRow = document.getElementById('summary_loss_row');
            if (summaryLossRow) {
                summaryLossRow.style.display = isCompanyLoss ? 'flex' : 'none';
                document.getElementById('summary_loss').textContent = isCompanyLoss ? 'Yes' : 'No';
            }
        }
        
        function calculateTax() {
            if (!document.getElementById('has_tax') || !document.getElementById('has_tax').checked) return;
            
            const isRental = document.getElementById('is_rental').checked;
            const expense = isRental ? 
                (parseFloat(document.getElementById('rental_rate').value) || 0) : 
                (parseFloat(document.getElementById('expense').value) || 0);
            
            const taxAmount = expense * 0.12;
            document.getElementById('tax').value = taxAmount.toFixed(2);
            document.getElementById('summary_tax').textContent = '₱' + taxAmount.toFixed(2);
        }
        
        function checkExpenseBudgetDifference() {
            const hasBudget = document.getElementById('has_budget').checked;
            if (!hasBudget) return;
            
            const expenseAmount = document.getElementById('is_rental').checked 
                ? parseFloat(document.getElementById('rental_rate').value || 0) 
                : parseFloat(document.getElementById('expense').value || 0);
            const budgetAmount = parseFloat(document.getElementById('budget').value || 0);
            const billToClientCheckbox = document.getElementById('bill_to_client_checkbox');
            
            if (!billToClientCheckbox) return;
            
            if (expenseAmount > budgetAmount) {
                billToClientCheckbox.disabled = false;
            } else {
                billToClientCheckbox.disabled = true;
                billToClientCheckbox.checked = false;
                document.getElementById('bill_to_client').value = 'No';
                document.getElementById('summary_bill').textContent = 'No';
                document.getElementById('summary_bill_row').style.display = 'none';
            }
        }
        
        // Debug function to help identify modal issues
        function debugModals() {
            console.log("=== DEBUGGING MODALS ===");
            
            // Check for edit project modal by both IDs
            const editProjectModal = document.getElementById('editProjectModal');
            const editProjectPanel = document.getElementById('editProjectPanel');
            
            console.log("Edit project modal (by ID 'editProjectModal'):", editProjectModal);
            console.log("Edit project panel (by ID 'editProjectPanel'):", editProjectPanel);
            
            // Find all modals on the page
            const allModals = document.querySelectorAll('.modal');
            console.log("All modals on page:", allModals.length);
            allModals.forEach((modal, i) => {
                console.log(`Modal ${i+1}:`, {
                    id: modal.id,
                    classes: modal.className,
                    display: window.getComputedStyle(modal).display,
                    visibility: window.getComputedStyle(modal).visibility
                });
            });
            
            // Check if Bootstrap is available
            console.log("Bootstrap available:", typeof bootstrap !== 'undefined');
            console.log("Bootstrap Modal available:", typeof bootstrap !== 'undefined' && typeof bootstrap.Modal !== 'undefined');
            
            // Check for edit panel elements
            const editPanels = document.querySelectorAll('.edit-panel');
            console.log("Edit panel elements:", editPanels.length);
            
            // Check for ellipsis and edit project buttons
            console.log("Ellipsis buttons:", document.querySelectorAll('.ellipsis-btn').length);
            console.log("Edit project buttons:", document.querySelectorAll('.dropdown-edit').length);
            
            return "Modal debugging complete - check console for results";
        }
        
        // Make debugging function available globally
        window.debugModals = debugModals;
        
        // ======= EVENT DELEGATION =======
        
        // Use global event delegation for all button clicks
        document.addEventListener('click', function(e) {
            // ADD BUTTON
            if (e.target.matches('.add-record-btn') || e.target.closest('.add-record-btn') ||
                e.target.matches('#add-record-btn') || e.target.closest('#add-record-btn')) {
                e.preventDefault();
                console.log("Add button clicked via delegation!");
                openExpenseModal('add');
                return;
            }
            
            // EDIT RECORD BUTTON
            if (e.target.matches('.edit-btn') || e.target.closest('.edit-btn')) {
                e.preventDefault();
                console.log("Edit button clicked via delegation!");
                
                const button = e.target.matches('.edit-btn') ? e.target : e.target.closest('.edit-btn');
                
                const recordData = {
                    id: button.getAttribute('data-id'),
                    category: button.getAttribute('data-category'),
                    subcategory: button.getAttribute('data-subcategory'),
                    date: button.getAttribute('data-date'),
                    budget: button.getAttribute('data-budget'),
                    expense: button.getAttribute('data-expense'),
                    payee: button.getAttribute('data-payee'),
                    record_description: button.getAttribute('data-record_description'),
                    remarks: button.getAttribute('data-remarks'),
                    rental_rate: button.getAttribute('data-rental_rate') || 0,
                    tax: button.getAttribute('data-tax') || 0,
                    invoice_no: button.getAttribute('data-invoice_no') || '',
                    is_rental: button.getAttribute('data-is_rental') || 'No',
                    bill_to_client: button.getAttribute('data-bill_to_client') || 'No',
                    is_company_loss: button.getAttribute('data-is_company_loss') || 'No'
                };
                
                console.log("Record data for edit:", recordData);
                openExpenseModal('edit', recordData);
                return;
            }
            
            // DELETE RECORD BUTTON
            if (e.target.matches('.delete-btn') || e.target.closest('.delete-btn')) {
                e.preventDefault();
                console.log("Delete button clicked via delegation!");
                
                const row = e.target.closest('tr');
                if (!row) {
                    console.error("Could not find parent row!");
                    return;
                }
                
                const editBtn = row.querySelector('.edit-btn');
                if (editBtn) {
                    const recordId = editBtn.getAttribute('data-id');
                    console.log("Deleting record ID:", recordId);
                    deleteExpense(recordId);
                } else {
                    console.error("Could not find associated edit button with data-id!");
                }
                return;
            }
            
            // VIEW BUTTON
            if (e.target.matches('.view-btn') || e.target.closest('.view-btn')) {
                e.preventDefault();
                console.log("View button clicked via delegation!");
                
                const button = e.target.matches('.view-btn') ? e.target : e.target.closest('.view-btn');
                
                // Using jQuery to handle the view modal if available
                if (typeof $ !== 'undefined' && typeof bootstrap !== 'undefined') {
                    console.log("Using jQuery for view modal");
                    
                    // Let jQuery handle it (it's already working)
                    return;
                } else {
                    // Use our vanilla JS implementation
                    console.log("Using vanilla JS for view modal");
                    openViewExpenseModal(button);
                }
                return;
            }
            
            // ELLIPSIS BUTTON
            if (e.target.matches('.ellipsis-btn') || e.target.closest('.ellipsis-btn')) {
                e.preventDefault();
                e.stopPropagation(); // Prevent the document click handler from immediately closing the menu
                console.log("Ellipsis button clicked via delegation!");
                
                const button = e.target.matches('.ellipsis-btn') ? e.target : e.target.closest('.ellipsis-btn');
                toggleDropdown(button);
                return;
            }
            
            // EDIT PROJECT BUTTON
            if (e.target.matches('.dropdown-edit') || e.target.closest('.dropdown-edit')) {
                e.preventDefault();
                console.log("Edit project button clicked via delegation!");
                
                // Close dropdown menu first
                const menu = e.target.closest('.dropdown-menu');
                if (menu) {
                    menu.style.display = 'none';
                }
                
                // Open the edit project modal
                openEditProjectModal();
                return;
            }
            // DELETE PROJECT BUTTON
            if (e.target.matches('.dropdown-delete') || e.target.closest('.dropdown-delete')) {
                e.preventDefault();
                console.log("Delete project button clicked via delegation!");
                
                const button = e.target.matches('.dropdown-delete') ? e.target : e.target.closest('.dropdown-delete');
                const projectId = button.getAttribute('data-project-id');
                
                if (projectId) {
                    deleteProject(projectId);
                } else {
                    console.error("No project ID found on delete button!");
                }
                return;
            }
            
            // CLOSE MODAL/PANEL BUTTONS
            if (e.target.matches('.btn-close') || e.target.matches('[data-bs-dismiss="modal"]')) {
                console.log("Close button clicked");
                
                // Find the closest modal or panel
                const modal = e.target.closest('.modal');
                const panel = e.target.closest('.edit-panel');
                
                // Handle different modal/panel types
                if (modal) {
                    // Try Bootstrap's dismiss method first
                    if (typeof bootstrap !== 'undefined' && typeof bootstrap.Modal !== 'undefined') {
                        try {
                            const bsModal = bootstrap.Modal.getInstance(modal);
                            if (bsModal) {
                                bsModal.hide();
                            } else {
                                // Fallback
                                modal.style.display = 'none';
                                modal.classList.remove('show');
                                document.body.classList.remove('modal-open');
                            }
                        } catch (error) {
                            console.error("Error closing modal:", error);
                            // Fallback
                            modal.style.display = 'none';
                            modal.classList.remove('show');
                            document.body.classList.remove('modal-open');
                        }
                    } else {
                        // Fallback if Bootstrap is not available
                        modal.style.display = 'none';
                        modal.classList.remove('show');
                        document.body.classList.remove('modal-open');
                    }
                } else if (panel) {
                    // Handle custom panel closing
                    panel.classList.remove('open');
                }
            }
        });
        
        // Close dropdowns when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.project-options')) {
                document.querySelectorAll('.dropdown-menu').forEach(menu => {
                    menu.style.display = 'none';
                });
            }
        });
        
        // ======= FORM EVENTS =======
        
        // Category change event
        const categorySelect = document.getElementById('category');
        if (categorySelect) {
            categorySelect.addEventListener('change', function() {
                const subcategorySelect = document.getElementById('subcategory');
                if (!subcategorySelect) {
                    console.error("Subcategory select element not found");
                    return;
                }
                
                // Clear options
                subcategorySelect.innerHTML = '<option value="">Select Subcategory</option>';
                
                // Get selected category
                const selectedCategory = this.value;
                if (!selectedCategory) {
                    subcategorySelect.disabled = true;
                    return;
                }
                
                // Get subcategories from global variable
                let subcategories = [];
                if (typeof subcategoriesData !== 'undefined') {
                    subcategories = subcategoriesData;
                } else if (typeof window.subcategories !== 'undefined') {
                    subcategories = window.subcategories;
                } else {
                    console.error("Subcategories data not available - check if subcategoriesData or window.subcategories is defined");
                    return;
                }
                
                console.log("Selected category:", selectedCategory);
                console.log("Available subcategories:", subcategories);
                
                // Filter subcategories for the selected category
                const filteredSubcategories = subcategories.filter(subcat => 
                    subcat.category_name === selectedCategory
                );
                
                console.log("Filtered subcategories:", filteredSubcategories);
                
                if (filteredSubcategories && filteredSubcategories.length > 0) {
                    subcategorySelect.disabled = false;
                    subcategorySelect.setAttribute('required', 'required');
                    
                    // Add options
                    filteredSubcategories.forEach(subcat => {
                        const option = document.createElement('option');
                        option.value = subcat.subcategory_name;
                        option.textContent = subcat.subcategory_name;
                        subcategorySelect.appendChild(option);
                    });
                } else {
                    // If no subcategories found but category is selected
                    console.log("No subcategories found for category:", selectedCategory);
                    subcategorySelect.disabled = true;
                    subcategorySelect.removeAttribute('required');
                }
            });
        }
        
        // Form checkbox events
        const formCheckboxes = {
            'is_rental': function() {
                const isRental = this.checked;
                document.getElementById('expense_input').style.display = isRental ? 'none' : 'block';
                document.getElementById('rental_input').style.display = isRental ? 'block' : 'none';
                document.getElementById('summary_expense_row').style.display = isRental ? 'none' : 'flex';
                document.getElementById('summary_rental_row').style.display = isRental ? 'flex' : 'none';
                
                const expenseInput = document.getElementById('expense');
                const rentalInput = document.getElementById('rental_rate');
                
                if (isRental) {
                    expenseInput.value = '';
                    expenseInput.required = false;
                    rentalInput.required = true;
                    document.getElementById('is_rental_field').value = 'Yes';
                } else {
                    rentalInput.value = '';
                    rentalInput.required = false;
                    expenseInput.required = true;
                    document.getElementById('is_rental_field').value = 'No';
                }
                
                updateCalculations();
            },
            'has_budget': function() {
                const hasBudget = this.checked;
                const budgetInput = document.getElementById('budget');
                const billToClientCheckbox = document.getElementById('bill_to_client_checkbox');
                
                budgetInput.disabled = !hasBudget;
                budgetInput.required = hasBudget;
                document.getElementById('summary_budget_row').style.display = hasBudget ? 'flex' : 'none';
                document.getElementById('summary_variance_row').style.display = hasBudget ? 'flex' : 'none';
                document.getElementById('has_budget_field').value = hasBudget ? 'on' : 'off';
                
                if (hasBudget) {
                    // Disable bill to client initially when budget is checked
                    if (billToClientCheckbox) {
                        billToClientCheckbox.disabled = true;
                        billToClientCheckbox.checked = false;
                    }
                    document.getElementById('bill_to_client').value = 'No';
                    document.getElementById('summary_bill').textContent = 'No';
                    document.getElementById('summary_bill_row').style.display = 'none';
                    
                    // Check if expense exceeds budget
                    setTimeout(function() {
                        checkExpenseBudgetDifference();
                    }, 100);
                } else {
                    budgetInput.value = '';
                    
                    if (billToClientCheckbox) {
                        billToClientCheckbox.disabled = false;
                    }
                }
                
                updateCalculations();
                updateCompanyLossStatus();
            },
            'bill_to_client_checkbox': function() {
                const isBillToClient = this.checked;
                document.getElementById('bill_to_client').value = isBillToClient ? 'Yes' : 'No';
                document.getElementById('summary_bill').textContent = isBillToClient ? 'Yes' : 'No';
                document.getElementById('summary_bill_row').style.display = isBillToClient ? 'flex' : 'none';
                
                updateCompanyLossStatus();
            },
            'has_tax': function() {
                const hasTax = this.checked;
                const taxInput = document.getElementById('tax');
                const taxEditBtn = document.getElementById('tax_edit_btn');
                
                taxInput.disabled = true;
                document.getElementById('summary_tax_row').style.display = hasTax ? 'flex' : 'none';
                
                if (hasTax) {
                    if (taxEditBtn) taxEditBtn.style.display = 'block';
                    calculateTax();
                } else {
                    taxInput.value = '';
                    if (taxEditBtn) taxEditBtn.style.display = 'none';
                }
                
                updateCalculations();
            },
            'has_invoice': function() {
                const hasInvoice = this.checked;
                const invoiceInput = document.getElementById('invoice_no');
                
                invoiceInput.disabled = !hasInvoice;
                invoiceInput.required = hasInvoice;
                
                if (!hasInvoice) {
                    invoiceInput.value = '';
                }
            }
        };
        
        // Attach checkbox events
        Object.keys(formCheckboxes).forEach(id => {
            const checkbox = document.getElementById(id);
            if (checkbox) {
                checkbox.addEventListener('change', formCheckboxes[id]);
            }
        });
        
        // Tax edit button
        const taxEditBtn = document.getElementById('tax_edit_btn');
        if (taxEditBtn) {
            taxEditBtn.addEventListener('click', function() {
                const taxInput = document.getElementById('tax');
                const taxEditIcon = document.getElementById('tax_edit_icon');
                
                if (taxInput.disabled) {
                    taxInput.disabled = false;
                    taxEditIcon.src = 'icons/pencil-white.svg';
                    this.classList.remove('btn-outline-secondary');
                    this.classList.add('btn-primary');
                } else {
                    taxInput.disabled = true;
                    taxEditIcon.src = 'icons/pencil-black.svg';
                    this.classList.remove('btn-primary');
                    this.classList.add('btn-outline-secondary');
                    calculateTax();
                }
            });
        }
        
        // Input change events for calculations
        const calculationInputs = document.querySelectorAll('.calculation');
        calculationInputs.forEach(input => {
            input.addEventListener('input', function() {
                if (this.id === 'expense' || this.id === 'rental_rate' || this.id === 'budget') {
                    calculateTax();
                    checkExpenseBudgetDifference();
                    updateCompanyLossStatus();
                }
                updateCalculations();
            });
        });
        
        // Form submission
        const expenseForm = document.getElementById('expenseForm');
        if (expenseForm) {
            expenseForm.addEventListener('submit', function(e) {
                e.preventDefault();
                console.log("Form submitted");
                
                if (!this.checkValidity()) {
                    console.log("Form validation failed");
                    this.classList.add('was-validated');
                    return false;
                }
                
                // Check if tax is greater than expense
                const hasTax = document.getElementById('has_tax').checked;
                if (hasTax) {
                    const isRental = document.getElementById('is_rental').checked;
                    const expense = isRental ? 
                        (parseFloat(document.getElementById('rental_rate').value) || 0) : 
                        (parseFloat(document.getElementById('expense').value) || 0);
                    const tax = parseFloat(document.getElementById('tax').value) || 0;
                    
                    if (tax > expense) {
                        console.log("Tax validation failed: tax > expense");
                        alert("Tax cannot be greater than expense.");
                        return false;
                    }
                }
                
                console.log("Form validation passed, submitting");
                this.submit();
            });
        }
        
        // ======= INITIALIZATION =======
        
        // Run debug on page load
        debugModals();
        
        // Log DOM state
        console.log("=== DOM STATE ===");
        console.log("Project ID:", projectId);
        console.log("Add button:", document.querySelector('.add-record-btn') || document.getElementById('add-record-btn'));
        console.log("Edit buttons:", document.querySelectorAll('.edit-btn').length);
        console.log("Delete buttons:", document.querySelectorAll('.delete-btn').length);
        console.log("View buttons:", document.querySelectorAll('.view-btn').length);
        console.log("Project options:", document.querySelector('.project-options'));
        
        console.log("=== PROJECT BUTTONS SCRIPT FULLY LOADED ===");
    });
})();