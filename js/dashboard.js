// Dashboard JavaScript enhancements

document.addEventListener('DOMContentLoaded', function() {
    
    // Add loading states to tables
    function addLoadingState() {
        const tables = document.querySelectorAll('.table-responsive');
        tables.forEach(table => {
            table.classList.add('loading');
        });
    }
    
    // Remove loading states
    function removeLoadingState() {
        const tables = document.querySelectorAll('.table-responsive');
        tables.forEach(table => {
            table.classList.remove('loading');
        });
    }
    
    // Format currency values
    function formatCurrency(amount) {
        return new Intl.NumberFormat('en-PH', {
            style: 'currency',
            currency: 'PHP'
        }).format(amount);
    }
    
    // Animate KPI cards on scroll
    function animateKPICards() {
        const kpiCards = document.querySelectorAll('.kpi-card');
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.animationDelay = `${Array.from(kpiCards).indexOf(entry.target) * 0.1}s`;
                    entry.target.classList.add('animate-in');
                }
            });
        }, { threshold: 0.1 });
        
        kpiCards.forEach(card => observer.observe(card));
    }
    
    // Add click handlers for interactive elements
    function addInteractiveHandlers() {
        // KPI card click handlers
        const kpiCards = document.querySelectorAll('.kpi-card');
        kpiCards.forEach(card => {
            card.style.cursor = 'pointer';
            card.addEventListener('click', function() {
                // Add ripple effect
                this.classList.add('clicked');
                setTimeout(() => {
                    this.classList.remove('clicked');
                }, 300);
            });
        });
        
        // Table row click handlers
        const tableRows = document.querySelectorAll('.table tbody tr');
        tableRows.forEach(row => {
            row.style.cursor = 'pointer';
            row.addEventListener('click', function() {
                // Highlight selected row
                tableRows.forEach(r => r.classList.remove('selected'));
                this.classList.add('selected');
            });
        });
    }
    
    // Chart responsiveness
    function handleChartResize() {
        window.addEventListener('resize', function() {
            if (typeof categoryChart !== 'undefined') {
                categoryChart.resize();
            }
            if (typeof monthlyChart !== 'undefined') {
                monthlyChart.resize();
            }
        });
    }
    
    // Auto-refresh data (optional)
    function setupAutoRefresh() {
        // Refresh every 5 minutes (300000 ms)
        setInterval(function() {
            // Only refresh if the tab is visible
            if (!document.hidden) {
                updateDashboardData();
            }
        }, 300000);
    }
    
    // Update dashboard data via AJAX
    function updateDashboardData() {
        fetch('ajax/dashboard_update.php')
            .then(response => response.json())
            .then(data => {
                updateKPICards(data.kpi);
                updateCharts(data.charts);
                updateRecentTransactions(data.recent);
            })
            .catch(error => {
                console.log('Dashboard update failed:', error);
            });
    }
    
    // Update KPI cards with new data
    function updateKPICards(kpiData) {
        if (!kpiData) return;
        
        const totalExpenses = document.querySelector('.kpi-card:nth-child(1) h3');
        const totalTransactions = document.querySelector('.kpi-card:nth-child(2) h3');
        const avgTransaction = document.querySelector('.kpi-card:nth-child(3) h3');
        
        if (totalExpenses) {
            animateNumberChange(totalExpenses, kpiData.total_expenses);
        }
        if (totalTransactions) {
            animateNumberChange(totalTransactions, kpiData.total_transactions);
        }
        if (avgTransaction) {
            animateNumberChange(avgTransaction, kpiData.avg_transaction);
        }
    }
    
    // Animate number changes
    function animateNumberChange(element, newValue) {
        const currentValue = parseFloat(element.textContent.replace(/[₱,]/g, ''));
        const difference = newValue - currentValue;
        const steps = 30;
        const stepValue = difference / steps;
        let currentStep = 0;
        
        const animation = setInterval(() => {
            currentStep++;
            const displayValue = currentValue + (stepValue * currentStep);
            
            if (element.textContent.includes('₱')) {
                element.textContent = formatCurrency(displayValue);
            } else {
                element.textContent = Math.round(displayValue).toLocaleString();
            }
            
            if (currentStep >= steps) {
                clearInterval(animation);
                if (element.textContent.includes('₱')) {
                    element.textContent = formatCurrency(newValue);
                } else {
                    element.textContent = Math.round(newValue).toLocaleString();
                }
            }
        }, 50);
    }
    
    // Update charts with new data
    function updateCharts(chartData) {
        if (!chartData) return;
        
        // Update category chart
        if (typeof categoryChart !== 'undefined' && chartData.category) {
            categoryChart.data.labels = chartData.category.map(item => item.category);
            categoryChart.data.datasets[0].data = chartData.category.map(item => parseFloat(item.total_expense));
            categoryChart.update('none'); // No animation for updates
        }
        
        // Update monthly chart
        if (typeof monthlyChart !== 'undefined' && chartData.monthly) {
            monthlyChart.data.labels = chartData.monthly.map(item => {
                const date = new Date(item.month + '-01');
                return date.toLocaleDateString('en-US', { month: 'short', year: 'numeric' });
            });
            monthlyChart.data.datasets[0].data = chartData.monthly.map(item => parseFloat(item.total_expense));
            monthlyChart.update('none');
        }
    }
    
    // Update recent transactions table
    function updateRecentTransactions(recentData) {
        if (!recentData) return;
        
        const tbody = document.querySelector('.data-table-container:nth-of-type(2) tbody');
        if (!tbody) return;
        
        tbody.innerHTML = '';
        recentData.forEach(transaction => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>
                    <strong>${escapeHtml(transaction.record_description)}</strong>
                    ${transaction.subcategory ? `<br><small class="text-muted">${escapeHtml(transaction.subcategory)}</small>` : ''}
                </td>
                <td>₱${parseFloat(transaction.expense).toLocaleString('en-PH', {minimumFractionDigits: 2})}</td>
                <td>
                    <span class="category-badge category-${transaction.category.toLowerCase()}">
                        ${escapeHtml(transaction.category)}
                    </span>
                </td>
                <td>${new Date(transaction.purchase_date).toLocaleDateString('en-US', {month: 'short', day: 'numeric', year: 'numeric'})}</td>
                <td>${escapeHtml(transaction.project_name || 'N/A')}</td>
            `;
            tbody.appendChild(row);
        });
        
        // Re-add click handlers to new rows
        addInteractiveHandlers();
    }
    
    // Utility function to escape HTML
    function escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }
    
    // Tooltip functionality for truncated text
    function addTooltips() {
        const truncatedElements = document.querySelectorAll('[data-bs-toggle="tooltip"]');
        truncatedElements.forEach(element => {
            new bootstrap.Tooltip(element);
        });
    }
    
    // Search and filter functionality
    function addFilterFunctionality() {
        // Add search box to tables
        const tableContainers = document.querySelectorAll('.data-table-container');
        tableContainers.forEach(container => {
            const searchBox = document.createElement('div');
            searchBox.className = 'mb-3';
            searchBox.innerHTML = `
                <input type="text" class="form-control form-control-sm" placeholder="Search..." 
                       onkeyup="filterTable(this)">
            `;
            container.insertBefore(searchBox, container.querySelector('.table-responsive'));
        });
    }
    
    // Filter table rows based on search input
    window.filterTable = function(input) {
        const filter = input.value.toLowerCase();
        const table = input.closest('.data-table-container').querySelector('table');
        const rows = table.querySelectorAll('tbody tr');
        
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(filter) ? '' : 'none';
        });
    };
    
    // Print functionality
    function addPrintFunctionality() {
        const printButton = document.createElement('button');
        printButton.className = 'btn btn-outline-secondary btn-sm';
        printButton.innerHTML = '🖨️ Print Dashboard';
        printButton.onclick = () => window.print();
        
        const dashboardHeader = document.querySelector('.dashboard-header');
        if (dashboardHeader) {
            dashboardHeader.appendChild(printButton);
        }
    }
    
    // Export data functionality
    function addExportFunctionality() {
        const exportButton = document.createElement('button');
        exportButton.className = 'btn btn-outline-primary btn-sm ms-2';
        exportButton.innerHTML = '📊 Export Data';
        exportButton.onclick = exportDashboardData;
        
        const printButton = document.querySelector('.dashboard-header button');
        if (printButton) {
            printButton.parentNode.insertBefore(exportButton, printButton.nextSibling);
        }
    }
    
    // Export dashboard data to CSV
    function exportDashboardData() {
        const data = [];
        const tables = document.querySelectorAll('.data-table-container table');
        
        tables.forEach((table, index) => {
            const headers = Array.from(table.querySelectorAll('thead th')).map(th => th.textContent.trim());
            const rows = Array.from(table.querySelectorAll('tbody tr')).map(row => 
                Array.from(row.querySelectorAll('td')).map(td => td.textContent.trim())
            );
            
            data.push({
                name: table.closest('.data-table-container').querySelector('h4').textContent,
                headers: headers,
                rows: rows
            });
        });
        
        // Convert to CSV and download
        downloadCSV(data);
    }
    
    // Download CSV file
    function downloadCSV(data) {
        let csvContent = '';
        
        data.forEach(table => {
            csvContent += `\n${table.name}\n`;
            csvContent += table.headers.join(',') + '\n';
            table.rows.forEach(row => {
                csvContent += row.map(cell => `"${cell.replace(/"/g, '""')}"`).join(',') + '\n';
            });
            csvContent += '\n';
        });
        
        const blob = new Blob([csvContent], { type: 'text/csv' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `dashboard_export_${new Date().toISOString().split('T')[0]}.csv`;
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(url);
        document.body.removeChild(a);
    }
    
    // Initialize all functionality
    function initializeDashboard() {
        animateKPICards();
        addInteractiveHandlers();
        handleChartResize();
        addTooltips();
        addFilterFunctionality();
        addPrintFunctionality();
        addExportFunctionality();
        // setupAutoRefresh(); // Uncomment if you want auto-refresh
    }
    
    // Start initialization
    initializeDashboard();
    
    // Add page visibility change handler
    document.addEventListener('visibilitychange', function() {
        if (!document.hidden) {
            // Page became visible, optionally refresh data
            console.log('Dashboard is now visible');
        }
    });
    
});