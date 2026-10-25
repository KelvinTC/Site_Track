<template>
    <AppLayout>
        <!-- Page Header -->
        <div class="page-header">
            <div class="header-content">
                <div class="header-title">
                    <h1>Invoices</h1>
                    <p class="header-subtitle">Create, manage invoices and record payments</p>
                </div>
                <div class="header-actions">
                    <div class="search-box">
                        <i class="bi bi-search search-icon"></i>
                        <input v-model="searchQuery" type="text" placeholder="Search invoices..." class="search-input" />
                    </div>
                    <div class="filter-dropdown">
                        <select v-model="statusFilter" class="status-filter">
                            <option value="all">All Invoices</option>
                            <option value="paid">Paid</option>
                            <option value="unpaid">Unpaid</option>
                            <option value="partial">Partially Paid</option>
                        </select>
                    </div>
                    <a href="#" @click.prevent="api.download(api.exports.invoicesExcel())" class="btn btn-secondary">
                        <i class="bi bi-file-earmark-excel"></i>
                        <span>Export</span>
                    </a>
                    <button @click="openCreateModal" class="btn btn-primary">
                        <i class="bi bi-plus-lg"></i>
                        <span>New Invoice</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon icon-primary">
                    <i class="bi bi-file-text-fill"></i>
                </div>
                <div class="stat-value">{{ invoices.length }}</div>
                <div class="stat-label">Total Invoices</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon icon-warning">
                    <i class="bi bi-clock-fill"></i>
                </div>
                <div class="stat-value">${{ formatNumber(unpaidTotal) }}</div>
                <div class="stat-label">Outstanding</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon icon-success">
                    <i class="bi bi-check-circle-fill"></i>
                </div>
                <div class="stat-value">${{ formatNumber(paidTotal) }}</div>
                <div class="stat-label">Collected</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon icon-danger">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                </div>
                <div class="stat-value">{{ overdueCount }}</div>
                <div class="stat-label">Overdue</div>
            </div>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="loading-container">
            <div class="loader"></div>
            <p class="loading-text">Loading invoices...</p>
        </div>

        <!-- Invoices Card -->
        <div v-else class="card">
            <div class="card-header card-header-primary">
                <div class="header-left">
                    <i class="bi bi-receipt"></i>
                    <span>Invoice List</span>
                </div>
                <span class="invoice-count">{{ filteredInvoices.length }} {{ filteredInvoices.length === 1 ? 'invoice' : 'invoices' }}</span>
            </div>
            <div class="card-body p-0">
                <div v-if="filteredInvoices.length === 0" class="empty-state">
                    <div class="empty-state-icon">
                        <i class="bi bi-receipt"></i>
                    </div>
                    <h3 class="empty-state-title">No invoices found</h3>
                    <p class="empty-state-description">Create your first invoice to start billing.</p>
                    <button @click="openCreateModal" class="btn btn-primary">
                        <i class="bi bi-plus-lg"></i>
                        Create First Invoice
                    </button>
                </div>

                <div v-else class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Invoice #</th>
                                <th>Customer</th>
                                <th>Job</th>
                                <th class="text-right">Total</th>
                                <th class="text-right">Paid</th>
                                <th class="text-right">Balance</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Due Date</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="invoice in filteredInvoices" :key="invoice.id">
                                <td>
                                    <span class="invoice-number">{{ invoice.invoice_number }}</span>
                                </td>
                                <td>
                                    <div class="customer-cell">
                                        <div class="customer-avatar">
                                            {{ getInitials(invoice.customer?.name) }}
                                        </div>
                                        <span>{{ invoice.customer?.name || 'Unknown' }}</span>
                                    </div>
                                </td>
                                <td>
                                    <span class="job-ref">{{ invoice.siteJob?.job_number || invoice.site_job?.job_number || '-' }}</span>
                                </td>
                                <td class="text-right">
                                    <span class="total-amount">${{ formatNumber(invoice.total) }}</span>
                                </td>
                                <td class="text-right">
                                    <span class="paid-amount">${{ formatNumber(invoice.amount_paid || 0) }}</span>
                                </td>
                                <td class="text-right">
                                    <span :class="['balance-amount', { 'text-danger': getBalance(invoice) > 0 }]">
                                        ${{ formatNumber(getBalance(invoice)) }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span :class="['status-badge', 'status-' + invoice.status]">
                                        {{ getStatusLabel(invoice.status) }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span class="date-cell">{{ invoice.due_date ? formatDate(invoice.due_date) : '-' }}</span>
                                </td>
                                <td class="text-right">
                                    <div class="action-buttons">
                                        <button v-if="invoice.status !== 'paid'" @click="openPaymentModal(invoice)" class="btn btn-sm btn-success-soft" title="Record Payment">
                                            <i class="bi bi-cash"></i>
                                        </button>
                                        <button @click="viewInvoice(invoice)" class="btn btn-sm btn-ghost" title="View">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                        <button @click="openEmailModal(invoice)" class="btn btn-sm btn-ghost" title="Email Invoice">
                                            <i class="bi bi-envelope"></i>
                                        </button>
                                        <a href="#" @click.prevent="api.download(api.exports.invoicePdf(invoice.id))" class="btn btn-sm btn-ghost" title="Download PDF">
                                            <i class="bi bi-file-pdf"></i>
                                        </a>
                                        <button @click="deleteInvoice(invoice.id)" class="btn btn-sm btn-ghost btn-ghost-danger" title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Create Invoice Modal -->
        <Transition name="modal">
            <div v-if="showModal" class="modal-backdrop" @click.self="closeModal">
                <div class="modal-content modal-lg">
                    <div class="modal-header modal-header-primary">
                        <h5>Create Invoice</h5>
                        <button @click="closeModal" class="modal-close">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                    <form @submit.prevent="saveInvoice" class="modal-body">
                        <div class="form-group">
                            <label class="form-label">Job *</label>
                            <select v-model="form.site_job_id" required class="form-control">
                                <option value="">Select a job</option>
                                <option v-for="job in jobs" :key="job.id" :value="job.id">
                                    {{ job.job_number }} - {{ job.title }} ({{ job.customer?.name }})
                                </option>
                            </select>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Issue Date *</label>
                                <input v-model="form.issue_date" type="date" required class="form-control" />
                            </div>
                            <div class="form-group">
                                <label class="form-label">Due Date *</label>
                                <input v-model="form.due_date" type="date" required class="form-control" />
                            </div>
                        </div>

                        <!-- Line Items -->
                        <div class="line-items-section">
                            <div class="line-items-header">
                                <span class="line-items-title">Line Items</span>
                                <button type="button" @click="addLineItem" class="btn btn-sm btn-primary">
                                    <i class="bi bi-plus"></i> Add Item
                                </button>
                            </div>
                            <div class="line-items-body">
                                <div v-for="(item, index) in form.items" :key="index" class="line-item">
                                    <div class="line-item-grid">
                                        <div class="line-item-desc">
                                            <label v-if="index === 0" class="form-label-sm">Description</label>
                                            <input v-model="item.description" type="text" required class="form-control-sm" placeholder="Item description" />
                                        </div>
                                        <div class="line-item-type">
                                            <label v-if="index === 0" class="form-label-sm">Type</label>
                                            <select v-model="item.type" required class="form-control-sm">
                                                <option value="labor">Labor</option>
                                                <option value="material">Material</option>
                                                <option value="other">Other</option>
                                            </select>
                                        </div>
                                        <div class="line-item-qty">
                                            <label v-if="index === 0" class="form-label-sm">Qty</label>
                                            <input v-model.number="item.quantity" type="number" min="0" step="0.01" required class="form-control-sm" />
                                        </div>
                                        <div class="line-item-price">
                                            <label v-if="index === 0" class="form-label-sm">Price</label>
                                            <input v-model.number="item.unit_price" type="number" step="0.01" min="0" required class="form-control-sm" />
                                        </div>
                                        <div class="line-item-total">
                                            <label v-if="index === 0" class="form-label-sm">Total</label>
                                            <span class="item-total">${{ formatNumber(item.quantity * item.unit_price) }}</span>
                                        </div>
                                        <div class="line-item-action">
                                            <button type="button" @click="removeLineItem(index)" :disabled="form.items.length === 1" class="btn-remove">
                                                <i class="bi bi-x"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="line-items-footer">
                                <div class="totals-summary">
                                    <div class="summary-row">
                                        <span>Subtotal:</span>
                                        <span>${{ formatNumber(subtotal) }}</span>
                                    </div>
                                    <div class="summary-row" v-if="companyProfile.vat_registered">
                                        <span>VAT ({{ companyProfile.vat_rate || 15 }}%):</span>
                                        <span>${{ formatNumber(subtotal * (companyProfile.vat_rate || 15) / 100) }}</span>
                                    </div>
                                    <div class="summary-row total">
                                        <strong>Total:</strong>
                                        <strong>${{ formatNumber(companyProfile.vat_registered ? subtotal * (1 + (companyProfile.vat_rate || 15) / 100) : subtotal) }}</strong>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Notes</label>
                            <textarea v-model="form.notes" rows="2" class="form-control" placeholder="Optional notes for the invoice"></textarea>
                        </div>
                    </form>
                    <div class="modal-footer">
                        <button type="button" @click="closeModal" class="btn btn-secondary">Cancel</button>
                        <button @click="saveInvoice" :disabled="saving" class="btn btn-primary">
                            <span v-if="saving" class="spinner"></span>
                            <span v-else>
                                <i class="bi bi-check-lg"></i>
                                Create Invoice
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </Transition>

        <!-- View Invoice Modal -->
        <Transition name="modal">
            <div v-if="showViewModal" class="modal-backdrop" @click.self="showViewModal = false">
                <div class="modal-content modal-lg">
                    <div class="modal-header">
                        <h5>Invoice Details</h5>
                        <div class="header-actions-modal">
                            <a v-if="viewingInvoice" href="#" @click.prevent="api.download(api.exports.invoicePdf(viewingInvoice.id))" class="btn btn-sm btn-success">
                                <i class="bi bi-file-pdf"></i> PDF
                            </a>
                            <button @click="printInvoice" class="btn btn-sm btn-primary">
                                <i class="bi bi-printer"></i> Print
                            </button>
                        </div>
                    </div>
                    <div v-if="viewingInvoice" class="modal-body">
                        <!-- Invoice Preview -->
                        <div class="invoice-preview" id="invoice-print">
                            <!-- Invoice Header -->
                            <div class="invoice-header-row">
                                <div class="invoice-company">
                                    <img src="/images/logo.png" alt="Company Logo" class="company-logo-img" />
                                    <div class="company-details">
                                        <p class="company-name">{{ companyProfile.company_name }}</p>
                                        <p v-if="companyProfile.address">{{ companyProfile.address }}</p>
                                        <p v-if="companyProfile.city || companyProfile.state">{{ [companyProfile.city, companyProfile.state].filter(Boolean).join(', ') }}</p>
                                        <p v-if="companyProfile.phone">{{ companyProfile.phone }}</p>
                                        <p v-if="companyProfile.email">{{ companyProfile.email }}</p>
                                        <p v-if="companyProfile.vat_number" class="vat-number">VAT No: {{ companyProfile.vat_number }}</p>
                                    </div>
                                </div>
                                <div class="invoice-title-block">
                                    <h2 class="invoice-title">INVOICE</h2>
                                    <span :class="['status-badge', 'status-' + viewingInvoice.status]">
                                        {{ getStatusLabel(viewingInvoice.status) }}
                                    </span>
                                </div>
                            </div>

                            <!-- Invoice Info Row -->
                            <div class="invoice-info-row">
                                <div class="bill-to">
                                    <p class="detail-label">Bill To:</p>
                                    <p class="detail-value">{{ viewingInvoice.customer?.name }}</p>
                                    <p class="detail-sub">{{ viewingInvoice.customer?.address }}</p>
                                    <p v-if="viewingInvoice.customer?.city" class="detail-sub">{{ [viewingInvoice.customer?.city, viewingInvoice.customer?.state].filter(Boolean).join(', ') }}</p>
                                    <p v-if="viewingInvoice.customer?.vat_number" class="detail-sub vat-number">VAT: {{ viewingInvoice.customer?.vat_number }}</p>
                                </div>
                                <div class="invoice-meta">
                                    <div class="invoice-meta-item">
                                        <span class="meta-label">Invoice #</span>
                                        <span class="meta-value">{{ viewingInvoice.invoice_number }}</span>
                                    </div>
                                    <div class="invoice-meta-item">
                                        <span class="meta-label">Date</span>
                                        <span class="meta-value">{{ formatDate(viewingInvoice.issue_date) }}</span>
                                    </div>
                                    <div class="invoice-meta-item">
                                        <span class="meta-label">Due Date</span>
                                        <span class="meta-value">{{ viewingInvoice.due_date ? formatDate(viewingInvoice.due_date) : 'N/A' }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Items Table -->
                            <table class="invoice-table">
                                <thead>
                                    <tr>
                                        <th>Description</th>
                                        <th class="text-center">Type</th>
                                        <th class="text-right">Qty</th>
                                        <th class="text-right">Price</th>
                                        <th class="text-right">Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="item in viewingInvoice.items" :key="item.id">
                                        <td>{{ item.description }}</td>
                                        <td class="text-center"><span class="type-badge">{{ item.type }}</span></td>
                                        <td class="text-right">{{ item.quantity }}</td>
                                        <td class="text-right">${{ formatNumber(item.unit_price) }}</td>
                                        <td class="text-right font-semibold">${{ formatNumber(item.total || item.quantity * item.unit_price) }}</td>
                                    </tr>
                                </tbody>
                            </table>

                            <!-- Totals -->
                            <div class="invoice-totals">
                                <div class="totals-row">
                                    <span>Subtotal</span>
                                    <span>${{ formatNumber(viewingInvoice.subtotal) }}</span>
                                </div>
                                <div class="totals-row" v-if="viewingInvoice.vat_rate > 0">
                                    <span>VAT ({{ viewingInvoice.vat_rate }}%)</span>
                                    <span>${{ formatNumber(viewingInvoice.vat_amount || viewingInvoice.tax) }}</span>
                                </div>
                                <div class="totals-row total-final">
                                    <span>Total</span>
                                    <span class="total-value">${{ formatNumber(viewingInvoice.total) }}</span>
                                </div>
                                <div class="totals-row paid-row">
                                    <span>Amount Paid</span>
                                    <span class="text-success">${{ formatNumber(viewingInvoice.amount_paid || 0) }}</span>
                                </div>
                                <div class="totals-row balance-row">
                                    <span>Balance Due</span>
                                    <span :class="getBalance(viewingInvoice) > 0 ? 'text-danger' : 'text-success'">
                                        ${{ formatNumber(getBalance(viewingInvoice)) }}
                                    </span>
                                </div>
                            </div>

                            <!-- Notes -->
                            <div v-if="viewingInvoice.notes || companyProfile.invoice_notes" class="invoice-notes">
                                <p class="notes-label">Notes:</p>
                                <p v-if="viewingInvoice.notes">{{ viewingInvoice.notes }}</p>
                                <p v-if="companyProfile.invoice_notes">{{ companyProfile.invoice_notes }}</p>
                            </div>

                            <!-- Footer -->
                            <div class="invoice-footer">
                                {{ companyProfile.invoice_footer || 'Thank you for your business!' }}
                            </div>
                        </div>

                        <!-- Payment History -->
                        <div class="payment-history" v-if="viewingInvoice.payments?.length">
                            <h4><i class="bi bi-clock-history"></i> Payment History</h4>
                            <table class="payments-table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Method</th>
                                        <th>Reference</th>
                                        <th class="text-right">Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="payment in viewingInvoice.payments" :key="payment.id">
                                        <td>{{ formatDate(payment.payment_date) }}</td>
                                        <td><span class="method-badge">{{ formatMethod(payment.payment_method) }}</span></td>
                                        <td>{{ payment.reference_number || '-' }}</td>
                                        <td class="text-right text-success font-semibold">+${{ formatNumber(payment.amount) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Record Payment Button -->
                        <div v-if="viewingInvoice.status !== 'paid'" class="payment-action">
                            <button @click="openPaymentModal(viewingInvoice); showViewModal = false" class="btn btn-success btn-lg">
                                <i class="bi bi-cash"></i>
                                Record Payment
                            </button>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button @click="showViewModal = false" class="btn btn-secondary">Close</button>
                    </div>
                </div>
            </div>
        </Transition>

        <!-- Email Modal -->
        <Transition name="modal">
            <div v-if="showEmailModal" class="modal-backdrop" @click.self="closeEmailModal">
                <div class="modal-content">
                    <div class="modal-header modal-header-primary">
                        <h5>Email Invoice</h5>
                        <button @click="closeEmailModal" class="modal-close">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="email-invoice-summary" v-if="emailInvoice">
                            <div class="summary-header">
                                <span class="invoice-number">{{ emailInvoice.invoice_number }}</span>
                                <span class="customer-name">{{ emailInvoice.customer?.name }}</span>
                            </div>
                            <div class="summary-detail">
                                <span>Total: <strong>${{ formatNumber(emailInvoice.total) }}</strong></span>
                                <span v-if="emailInvoice.last_sent_at" class="last-sent">
                                    Last sent: {{ formatDate(emailInvoice.last_sent_at) }}
                                </span>
                            </div>
                        </div>

                        <form @submit.prevent="sendEmail">
                            <div class="form-group">
                                <label class="form-label">Recipient Email *</label>
                                <input v-model="emailForm.email" type="email" required class="form-control"
                                    placeholder="customer@example.com" />
                            </div>

                            <div class="form-group">
                                <label class="form-label">Custom Message (Optional)</label>
                                <textarea v-model="emailForm.message" rows="3" class="form-control"
                                    placeholder="Add a personalized message to include in the email..."></textarea>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" @click="closeEmailModal" class="btn btn-secondary">Cancel</button>
                        <button @click="sendEmail" :disabled="sendingEmail" class="btn btn-primary">
                            <span v-if="sendingEmail" class="spinner"></span>
                            <span v-else>
                                <i class="bi bi-send"></i>
                                Send Invoice
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </Transition>

        <!-- Payment Modal -->
        <Transition name="modal">
            <div v-if="showPaymentModal" class="modal-backdrop" @click.self="closePaymentModal">
                <div class="modal-content">
                    <div class="modal-header modal-header-success">
                        <h5>Record Payment</h5>
                        <button @click="closePaymentModal" class="modal-close">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                    <div class="modal-body">
                        <!-- Invoice Summary -->
                        <div class="payment-invoice-summary" v-if="paymentInvoice">
                            <div class="summary-header">
                                <span class="invoice-number">{{ paymentInvoice.invoice_number }}</span>
                                <span class="customer-name">{{ paymentInvoice.customer?.name }}</span>
                            </div>
                            <div class="summary-amounts">
                                <div class="amount-item">
                                    <span class="amount-label">Invoice Total</span>
                                    <span class="amount-value">${{ formatNumber(paymentInvoice.total) }}</span>
                                </div>
                                <div class="amount-item">
                                    <span class="amount-label">Previously Paid</span>
                                    <span class="amount-value text-success">${{ formatNumber(paymentInvoice.amount_paid || 0) }}</span>
                                </div>
                                <div class="amount-item highlight">
                                    <span class="amount-label">Balance Due</span>
                                    <span class="amount-value text-danger">${{ formatNumber(getBalance(paymentInvoice)) }}</span>
                                </div>
                            </div>
                        </div>

                        <form @submit.prevent="savePayment">
                            <div class="form-group">
                                <label class="form-label">Payment Amount *</label>
                                <div class="input-wrapper">
                                    <span class="input-prefix">$</span>
                                    <input v-model="paymentForm.amount" type="number" step="0.01" min="0" :max="getBalance(paymentInvoice)" required class="form-control with-prefix" placeholder="0.00" />
                                </div>
                                <div class="quick-amounts" v-if="paymentInvoice">
                                    <button type="button" @click="paymentForm.amount = getBalance(paymentInvoice)" class="quick-amount-btn">
                                        Full Balance (${{ formatNumber(getBalance(paymentInvoice)) }})
                                    </button>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Payment Date *</label>
                                <input v-model="paymentForm.payment_date" type="date" required class="form-control" />
                            </div>

                            <div class="form-group">
                                <label class="form-label">Payment Method</label>
                                <div class="method-options">
                                    <label class="method-option" :class="{ active: paymentForm.payment_method === 'cash' }">
                                        <input type="radio" v-model="paymentForm.payment_method" value="cash" />
                                        <i class="bi bi-cash-stack"></i>
                                        <span>Cash</span>
                                    </label>
                                    <label class="method-option" :class="{ active: paymentForm.payment_method === 'bank_transfer' }">
                                        <input type="radio" v-model="paymentForm.payment_method" value="bank_transfer" />
                                        <i class="bi bi-bank"></i>
                                        <span>Bank Transfer</span>
                                    </label>
                                    <label class="method-option" :class="{ active: paymentForm.payment_method === 'cheque' }">
                                        <input type="radio" v-model="paymentForm.payment_method" value="cheque" />
                                        <i class="bi bi-credit-card-2-front"></i>
                                        <span>Cheque</span>
                                    </label>
                                    <label class="method-option" :class="{ active: paymentForm.payment_method === 'mobile_money' }">
                                        <input type="radio" v-model="paymentForm.payment_method" value="mobile_money" />
                                        <i class="bi bi-phone"></i>
                                        <span>Mobile Money</span>
                                    </label>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Reference Number</label>
                                <input v-model="paymentForm.reference_number" type="text" class="form-control" placeholder="e.g., Transaction ID, Cheque #" />
                            </div>

                            <div class="form-group">
                                <label class="form-label">Notes</label>
                                <textarea v-model="paymentForm.notes" rows="2" class="form-control" placeholder="Add any notes about this payment..."></textarea>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" @click="closePaymentModal" class="btn btn-secondary">Cancel</button>
                        <button @click="savePayment" :disabled="savingPayment" class="btn btn-success">
                            <span v-if="savingPayment" class="spinner"></span>
                            <span v-else>
                                <i class="bi bi-check-lg"></i>
                                Record Payment
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import AppLayout from '../Layouts/AppLayout.vue';
import api from '../api';
import { toDateInput } from '@/utils/date';

const loading = ref(true);
const saving = ref(false);
const savingPayment = ref(false);
const sendingEmail = ref(false);
const invoices = ref([]);
const jobs = ref([]);
const companyProfile = ref({});
const searchQuery = ref('');
const statusFilter = ref('all');
const showModal = ref(false);
const showViewModal = ref(false);
const showPaymentModal = ref(false);
const showEmailModal = ref(false);
const viewingInvoice = ref(null);
const paymentInvoice = ref(null);
const emailInvoice = ref(null);
const formErrors = ref({});

const getEmptyForm = () => ({
    site_job_id: '',
    issue_date: toDateInput(),
    due_date: '',
    notes: '',
    items: [{ description: '', type: 'labor', quantity: 1, unit_price: 0 }],
});

const getEmptyPaymentForm = () => ({
    invoice_id: '',
    amount: '',
    payment_date: toDateInput(),
    payment_method: 'cash',
    reference_number: '',
    notes: '',
});

const getEmptyEmailForm = () => ({
    email: '',
    message: '',
});

const form = ref(getEmptyForm());
const paymentForm = ref(getEmptyPaymentForm());
const emailForm = ref(getEmptyEmailForm());

const filteredInvoices = computed(() => {
    let filtered = invoices.value;

    // Filter by status
    if (statusFilter.value !== 'all') {
        if (statusFilter.value === 'paid') {
            filtered = filtered.filter(i => i.status === 'paid');
        } else if (statusFilter.value === 'unpaid') {
            filtered = filtered.filter(i => ['unpaid', 'sent', 'overdue'].includes(i.status));
        } else if (statusFilter.value === 'partial') {
            filtered = filtered.filter(i => i.status === 'partial');
        }
    }

    // Filter by search query
    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        filtered = filtered.filter(i =>
            i.invoice_number?.toLowerCase().includes(query) ||
            i.customer?.name?.toLowerCase().includes(query)
        );
    }

    return filtered;
});

const subtotal = computed(() =>
    form.value.items.reduce((sum, item) => sum + (item.quantity * item.unit_price), 0)
);

const unpaidTotal = computed(() =>
    invoices.value
        .filter(i => ['unpaid', 'partial', 'sent', 'overdue'].includes(i.status))
        .reduce((sum, i) => sum + getBalance(i), 0)
);

const paidTotal = computed(() =>
    invoices.value.reduce((sum, i) => sum + parseFloat(i.amount_paid || 0), 0)
);

const overdueCount = computed(() =>
    invoices.value.filter(i => i.status === 'overdue').length
);

const formatNumber = (num) => new Intl.NumberFormat('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(num || 0);
const formatDate = (date) => new Date(date).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
const formatMethod = (method) => method?.split('_').map(w => w.charAt(0).toUpperCase() + w.slice(1)).join(' ') || 'Unknown';
const getInitials = (name) => name ? name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase() : '?';

const getBalance = (invoice) => {
    return parseFloat(invoice.total || 0) - parseFloat(invoice.amount_paid || 0);
};

const getStatusLabel = (status) => {
    const labels = {
        paid: 'Paid',
        unpaid: 'Unpaid',
        partial: 'Partial',
        sent: 'Sent',
        overdue: 'Overdue',
        draft: 'Draft',
    };
    return labels[status] || status || 'Unknown';
};

const loadData = async () => {
    try {
        loading.value = true;
        const [invoicesRes, jobsRes, profileRes] = await Promise.all([
            api.invoices.getAll(),
            api.jobs.getAll(),
            api.companyProfile.get(),
        ]);
        invoices.value = invoicesRes.data;
        jobs.value = jobsRes.data;
        companyProfile.value = profileRes.data;
    } catch (error) {
        console.error('Error loading data:', error);
    } finally {
        loading.value = false;
    }
};

const openCreateModal = () => {
    form.value = getEmptyForm();
    formErrors.value = {};
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
};

const viewInvoice = (invoice) => {
    viewingInvoice.value = invoice;
    showViewModal.value = true;
};

const openPaymentModal = (invoice) => {
    paymentInvoice.value = invoice;
    paymentForm.value = getEmptyPaymentForm();
    paymentForm.value.invoice_id = invoice.id;
    paymentForm.value.amount = getBalance(invoice);
    showPaymentModal.value = true;
};

const closePaymentModal = () => {
    showPaymentModal.value = false;
    paymentInvoice.value = null;
};

const openEmailModal = (invoice) => {
    emailInvoice.value = invoice;
    emailForm.value = getEmptyEmailForm();
    // Pre-fill with customer email if available
    if (invoice.customer?.email) {
        emailForm.value.email = invoice.customer.email;
    }
    showEmailModal.value = true;
};

const closeEmailModal = () => {
    showEmailModal.value = false;
    emailInvoice.value = null;
};

const addLineItem = () => {
    form.value.items.push({ description: '', type: 'labor', quantity: 1, unit_price: 0 });
};

const removeLineItem = (index) => {
    if (form.value.items.length > 1) {
        form.value.items.splice(index, 1);
    }
};

const saveInvoice = async () => {
    try {
        saving.value = true;
        formErrors.value = {};

        // Clean up data - convert empty strings to null
        const data = {
            ...form.value,
            due_date: form.value.due_date || null,
            notes: form.value.notes || null,
        };

        await api.invoices.create(data);
        await loadData();
        closeModal();
    } catch (error) {
        console.error('Error saving invoice:', error);
        if (error.response?.status === 422 && error.response?.data?.errors) {
            formErrors.value = error.response.data.errors;
            alert('Validation errors:\n' + Object.values(error.response.data.errors).flat().join('\n'));
        } else {
            alert('Error creating invoice. Please try again.');
        }
    } finally {
        saving.value = false;
    }
};

const savePayment = async () => {
    try {
        savingPayment.value = true;
        await api.payments.create(paymentForm.value);
        await loadData();
        closePaymentModal();
    } catch (error) {
        console.error('Error saving payment:', error);
        alert('Error recording payment. Please try again.');
    } finally {
        savingPayment.value = false;
    }
};

const sendEmail = async () => {
    if (!emailInvoice.value) return;

    try {
        sendingEmail.value = true;
        const response = await api.invoices.sendEmail(
            emailInvoice.value.id,
            emailForm.value.email,
            emailForm.value.message || null
        );
        await loadData();
        closeEmailModal();
        alert(response.data.message || 'Invoice sent successfully!');
    } catch (error) {
        console.error('Error sending email:', error);
        alert(error.response?.data?.message || 'Failed to send email. Please try again.');
    } finally {
        sendingEmail.value = false;
    }
};

const deleteInvoice = async (id) => {
    if (!confirm('Are you sure you want to delete this invoice?')) return;
    try {
        await api.invoices.delete(id);
        await loadData();
    } catch (error) {
        console.error('Error deleting invoice:', error);
    }
};

const printInvoice = () => {
    window.print();
};

// Expose api for template usage
const exportUrls = {
    invoicePdf: api.exports.invoicePdf,
    invoicesExcel: api.exports.invoicesExcel,
};

onMounted(() => {
    loadData();
});
</script>

<style scoped>

/* Filter Dropdown */
.filter-dropdown {
    min-width: 150px;
}

.status-filter {
    width: 100%;
    padding: 0.625rem 1rem;
    font-size: 0.9375rem;
    background: white;
    border: 1.5px solid var(--gray-200);
    border-radius: 0.75rem;
    cursor: pointer;
    transition: all 0.2s ease;
    appearance: none;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e");
    background-position: right 0.75rem center;
    background-repeat: no-repeat;
    background-size: 1.25rem;
    padding-right: 2.5rem;
}

.status-filter:focus {
    outline: none;
    border-color: var(--primary-600);
    box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1);
}

/* Stats Grid */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1.25rem;
    margin-bottom: 2.5rem;
}

@media (min-width: 768px) {
    .stats-grid {
        grid-template-columns: repeat(4, 1fr);
        gap: 1.75rem;
    }
}

/* Stat Card */
.stat-card {
    background: white;
    border-radius: 1.25rem;
    padding: 1.75rem 1.5rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06), 0 4px 12px rgba(0, 0, 0, 0.04);
    border: 1px solid rgba(0, 0, 0, 0.04);
    text-align: center;
    transition: all 0.25s ease;
    display: flex;
    flex-direction: column;
    align-items: center;
}

.stat-card:hover {
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    transform: translateY(-3px);
}

.stat-icon {
    width: 3rem;
    height: 3rem;
    border-radius: 0.75rem;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    margin: 0 auto 0.75rem;
}

.icon-primary { background: linear-gradient(135deg, var(--primary-50) 0%, var(--primary-100) 100%); color: var(--primary-600); }
.icon-warning { background: linear-gradient(135deg, var(--warning-100) 0%, var(--warning-200) 100%); color: var(--warning-600); }
.icon-success { background: linear-gradient(135deg, var(--success-100) 0%, var(--success-200) 100%); color: var(--success-600); }
.icon-danger { background: linear-gradient(135deg, var(--danger-100) 0%, var(--danger-200) 100%); color: var(--danger-600); }

.stat-value {
    font-size: 1.5rem;
    font-weight: 800;
    color: var(--gray-900);
}

.stat-label {
    font-size: 0.75rem;
    color: var(--gray-500);
    font-weight: 600;
    text-transform: uppercase;
    margin-top: 0.25rem;
}

@keyframes spin { to { transform: rotate(360deg); } }

.invoice-count {
    font-size: 0.8125rem;
    font-weight: 500;
    opacity: 0.9;
}

.invoice-number {
    font-weight: 700;
    color: var(--primary-600);
    font-family: monospace;
}

.job-ref { color: var(--gray-500); }

.total-amount {
    font-weight: 700;
    color: var(--gray-900);
}

.paid-amount {
    color: var(--success-600);
}

.balance-amount {
    font-weight: 600;
}

.text-danger { color: var(--danger-600); }

.date-cell { color: var(--gray-500); }

.status-paid {
    background: linear-gradient(135deg, var(--success-100) 0%, var(--success-200) 100%);
    color: var(--success-600);
}

.status-unpaid,
.status-sent {
    background: linear-gradient(135deg, var(--warning-100) 0%, var(--warning-200) 100%);
    color: var(--warning-600);
}

.status-partial {
    background: linear-gradient(135deg, var(--info-100) 0%, var(--info-200) 100%);
    color: var(--info-600);
}

.status-overdue {
    background: linear-gradient(135deg, var(--danger-100) 0%, var(--danger-200) 100%);
    color: var(--danger-600);
}

.status-draft {
    background: linear-gradient(135deg, var(--gray-100) 0%, var(--gray-200) 100%);
    color: var(--gray-500);
}

.btn-success-soft {
    padding: 0.5rem;
    color: var(--success-600);
    background: var(--success-100);
    border: none;
    border-radius: 0.5rem;
    cursor: pointer;
    transition: all 0.2s ease;
}

.btn-success-soft:hover { background: var(--success-200); }

.modal-lg { max-width: 600px; }

.header-actions-modal {
    display: flex;
    gap: 0.5rem;
}

.modal-header-primary { background: linear-gradient(135deg, var(--primary-600) 0%, var(--primary-700) 100%); }
.modal-header-primary h5 { color: white; }

.modal-header-success { background: linear-gradient(135deg, var(--success-600) 0%, var(--success-700) 100%); }
.modal-header-success h5 { color: white; }

.form-label-sm {
    font-size: 0.625rem;
    font-weight: 500;
    color: var(--gray-500);
    margin-bottom: 0.125rem;
}

.form-control-sm {
    width: 100%;
    padding: 0.375rem 0.5rem;
    font-size: 0.6875rem;
    background: white;
    border: 1px solid var(--gray-300);
    border-radius: 0.25rem;
}

.form-control-sm:focus {
    outline: none;
    border-color: var(--primary-600);
}

.input-wrapper {
    position: relative;
}

.input-prefix {
    position: absolute;
    left: 0.625rem;
    top: 50%;
    transform: translateY(-50%);
    color: var(--gray-500);
    font-weight: 500;
    font-size: 0.75rem;
}

.form-control.with-prefix {
    padding-left: 1.5rem;
}

/* Line Items */
.line-items-section {
    background: var(--gray-50);
    border-radius: 0.75rem;
    margin-bottom: 1rem;
    overflow: hidden;
}

.line-items-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem 1rem;
    background: var(--gray-100);
}

.line-items-title {
    font-weight: 600;
    font-size: 0.875rem;
}

.line-items-body { padding: 1rem; }

.line-item { margin-bottom: 0.75rem; }

.line-item-grid {
    display: grid;
    grid-template-columns: 2fr 1fr 1fr 1fr 1fr auto;
    gap: 0.5rem;
    align-items: end;
}

@media (max-width: 640px) {
    .line-item-grid {
        grid-template-columns: 1fr 1fr;
    }
}

.item-total {
    font-weight: 600;
    color: var(--gray-900);
    padding: 0.5rem;
    display: block;
}

.btn-remove {
    padding: 0.5rem;
    background: var(--danger-100);
    color: var(--danger-600);
    border: none;
    border-radius: 0.5rem;
    cursor: pointer;
}

.btn-remove:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.line-items-footer {
    padding: 0.75rem 1rem;
    background: var(--gray-100);
}

.totals-summary {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 0.25rem;
}

.summary-row {
    display: flex;
    justify-content: space-between;
    gap: 2rem;
    font-size: 0.8125rem;
    color: var(--gray-600);
}

.summary-row.total {
    border-top: 1px solid var(--gray-300);
    padding-top: 0.5rem;
    margin-top: 0.25rem;
    font-size: 0.9375rem;
    color: var(--gray-900);
}

/* Email Invoice Summary */
.email-invoice-summary {
    background: var(--gray-50);
    border-radius: 0.75rem;
    padding: 1rem;
    margin-bottom: 1rem;
}

.email-invoice-summary .summary-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.5rem;
}

.email-invoice-summary .summary-detail {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 0.875rem;
    color: var(--gray-500);
}

.email-invoice-summary .last-sent {
    font-size: 0.75rem;
    color: var(--success-600);
}

/* Payment Invoice Summary */
.payment-invoice-summary {
    background: var(--gray-50);
    border-radius: 0.75rem;
    padding: 1rem;
    margin-bottom: 1rem;
}

.summary-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.75rem;
    padding-bottom: 0.75rem;
    border-bottom: 1px solid var(--gray-200);
}

.summary-header .invoice-number {
    font-size: 1rem;
}

.summary-header .customer-name {
    color: var(--gray-500);
}

.summary-amounts {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.amount-item {
    display: flex;
    justify-content: space-between;
    font-size: 0.875rem;
}

.amount-item .amount-label {
    color: var(--gray-500);
}

.amount-item .amount-value {
    font-weight: 600;
}

.amount-item.highlight {
    background: white;
    margin: 0.5rem -1rem -1rem;
    padding: 0.75rem 1rem;
    border-radius: 0 0 0.75rem 0.75rem;
}

.amount-item.highlight .amount-label,
.amount-item.highlight .amount-value {
    font-weight: 700;
    font-size: 1rem;
}

.quick-amounts {
    margin-top: 0.5rem;
}

.quick-amount-btn {
    padding: 0.375rem 0.75rem;
    background: var(--primary-100);
    color: var(--primary-600);
    border: none;
    border-radius: 0.375rem;
    font-size: 0.75rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
}

.quick-amount-btn:hover {
    background: var(--primary-200);
}

/* Method Options */
.method-options {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 0.5rem;
}

.method-option {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.375rem;
    padding: 0.75rem 0.5rem;
    background: var(--gray-50);
    border: 1.5px solid var(--gray-200);
    border-radius: 0.5rem;
    cursor: pointer;
    transition: all 0.2s ease;
}

.method-option input {
    display: none;
}

.method-option i {
    font-size: 1.25rem;
    color: var(--gray-500);
}

.method-option span {
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--gray-500);
}

.method-option:hover {
    border-color: var(--primary-600);
}

.method-option.active {
    background: var(--primary-50);
    border-color: var(--primary-600);
}

.method-option.active i,
.method-option.active span {
    color: var(--primary-600);
}

/* Invoice Preview */
.invoice-preview { background: white; }

.invoice-header-row {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    padding-bottom: 1.5rem;
    border-bottom: 2px solid var(--primary-600);
    margin-bottom: 1.5rem;
}

.invoice-company {
    flex-shrink: 0;
}

.company-logo-img {
    width: 3rem;
    height: 3rem;
    border-radius: 0.25rem;
    object-fit: contain;
}

.company-details {
    font-size: 0.5625rem;
    color: var(--gray-600);
    line-height: 1.3;
}

.company-details .company-name {
    font-weight: 600;
    color: var(--gray-900);
    font-size: 0.6875rem;
}

.company-details p {
    margin: 0;
}

.invoice-title-block {
    text-align: right;
}

.invoice-title {
    font-size: 2rem;
    font-weight: 800;
    color: var(--primary-600);
    margin: 0;
    letter-spacing: 0.05em;
}

.invoice-info-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid var(--gray-200);
}

.invoice-meta {
    text-align: right;
}

.invoice-meta-item {
    display: flex;
    justify-content: flex-end;
    gap: 1rem;
    margin-bottom: 0.25rem;
}

.meta-label {
    color: var(--gray-500);
    font-size: 0.8125rem;
}

.meta-value {
    font-weight: 600;
    color: var(--gray-900);
    font-size: 0.875rem;
    min-width: 6rem;
    text-align: right;
}

.detail-label {
    font-size: 0.75rem;
    color: var(--gray-500);
    margin: 0;
}

.detail-value {
    font-weight: 600;
    color: var(--gray-900);
    margin: 0;
}

.detail-sub {
    font-size: 0.8125rem;
    color: var(--gray-500);
    margin: 0;
}

.invoice-table {
    width: 100%;
    font-size: 0.875rem;
    margin-bottom: 1rem;
}

.invoice-table th {
    background: var(--gray-50);
    padding: 0.625rem 0.75rem;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    color: var(--gray-500);
    border-bottom: 1px solid var(--gray-200);
}

.invoice-table td {
    padding: 0.625rem 0.75rem;
    border-bottom: 1px solid var(--gray-100);
}

.type-badge {
    background: var(--gray-100);
    color: var(--gray-600);
    padding: 0.125rem 0.5rem;
    border-radius: 9999px;
    font-size: 0.6875rem;
    text-transform: capitalize;
}

/* Invoice Totals */
.invoice-totals {
    margin-top: 1rem;
    border-top: 1px solid var(--gray-200);
    padding-top: 0.75rem;
}

.totals-row {
    display: flex;
    justify-content: space-between;
    padding: 0.375rem 0;
    font-size: 0.875rem;
    color: var(--gray-600);
}

.totals-row.total-final {
    border-top: 2px solid var(--gray-200);
    margin-top: 0.5rem;
    padding-top: 0.75rem;
    font-size: 1.125rem;
    font-weight: 700;
    color: var(--gray-900);
}

.totals-row.paid-row {
    background: var(--success-50);
    margin: 0.5rem -1rem 0;
    padding: 0.5rem 1rem;
}

.totals-row.balance-row {
    background: var(--danger-50);
    margin: 0 -1rem;
    padding: 0.5rem 1rem;
    font-weight: 700;
}

.total-value { color: var(--primary-600); }

.vat-number {
    font-weight: 600;
    color: var(--primary-600);
}

.invoice-notes {
    background: var(--gray-50);
    padding: 1rem;
    border-radius: 0.5rem;
    margin-top: 1rem;
    font-size: 0.875rem;
}

.notes-label {
    color: var(--gray-500);
    margin: 0 0 0.25rem;
    font-size: 0.75rem;
}

.invoice-footer {
    text-align: center;
    color: var(--gray-400);
    font-size: 0.875rem;
    margin-top: 1.5rem;
    padding-top: 1rem;
    border-top: 1px solid var(--gray-200);
}

/* Payment History */
.payment-history {
    margin-top: 1.5rem;
    padding-top: 1.5rem;
    border-top: 1px solid var(--gray-200);
}

.payment-history h4 {
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--gray-700);
    margin: 0 0 1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.payments-table {
    width: 100%;
    font-size: 0.8125rem;
}

.payments-table th {
    background: var(--gray-50);
    padding: 0.5rem 0.75rem;
    font-weight: 600;
    color: var(--gray-500);
    font-size: 0.6875rem;
    text-transform: uppercase;
}

.payments-table td {
    padding: 0.625rem 0.75rem;
    border-bottom: 1px solid var(--gray-100);
}

.method-badge {
    background: var(--primary-100);
    color: var(--primary-700);
    padding: 0.125rem 0.5rem;
    border-radius: 0.25rem;
    font-size: 0.6875rem;
    font-weight: 500;
}

/* Payment Action */
.payment-action {
    margin-top: 1.5rem;
    text-align: center;
}

.btn-lg {
    padding: 0.875rem 1.5rem;
    font-size: 1rem;
}

.font-semibold { font-weight: 600; }

@media print {
    .page-header,
    .stats-grid,
    .card,
    .loading-container,
    .payment-history,
    .payment-action {
        display: none !important;
    }

    .modal-backdrop {
        position: absolute;
        background: white !important;
        backdrop-filter: none;
    }

    .modal-content {
        box-shadow: none;
        max-height: none;
        border: none;
        width: 100%;
        max-width: 100%;
    }

    .modal-header,
    .modal-footer {
        display: none !important;
    }

    .modal-body {
        padding: 0;
    }

    .invoice-preview {
        padding: 0;
    }
}
</style>
