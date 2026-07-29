/**
 * @copyright Copyright (c) 2024 Metrat <disparam@gmail.com>
 *
 * @author Metrat <disparam@gmail.com>
 *
 * @license AGPL-3.0-or-later
 */

/**
 * AdminOffboard UI Components
 */
class AdminOffboardComponents {
    constructor() {
        this.components = {};
        this.init();
    }

    init() {
        this.registerComponent('modal', this.Modal);
        this.registerComponent('table', this.DataTable);
        this.registerComponent('form', this.Form);
        this.registerComponent('notification', this.Notification);
        this.registerComponent('progress', this.Progress);
        this.registerComponent('chart', this.Chart);
        this.registerComponent('search', this.Search);
        this.registerComponent('pagination', this.Pagination);
        this.registerComponent('tabs', this.Tabs);
        this.registerComponent('dropdown', this.Dropdown);
        this.registerComponent('tooltip', this.Tooltip);
    }

    registerComponent(name, component) {
        this.components[name] = component;
    }

    getComponent(name) {
        return this.components[name];
    }

    /**
     * Modal Component
     */
    Modal = class {
        constructor(options = {}) {
            this.options = {
                title: 'Modal',
                content: '',
                size: 'medium',
                closeOnOverlay: true,
                showFooter: true,
                confirmText: 'Confirm',
                cancelText: 'Cancel',
                ...options
            };
            this.element = null;
            this.create();
        }

        create() {
            const modal = document.createElement('div');
            modal.className = 'adminoffboard-modal';
            modal.innerHTML = `
                <div class="modal-overlay">
                    <div class="modal-container modal-${this.options.size}">
                        <div class="modal-header">
                            <h3>${this.options.title}</h3>
                            <button class="modal-close">&times;</button>
                        </div>
                        <div class="modal-body">
                            ${this.options.content}
                        </div>
                        ${this.options.showFooter ? `
                        <div class="modal-footer">
                            <button class="button button-secondary modal-cancel">${this.options.cancelText}</button>
                            <button class="button button-primary modal-confirm">${this.options.confirmText}</button>
                        </div>
                        ` : ''}
                    </div>
                </div>
            `;

            this.element = modal;
            document.body.appendChild(modal);

            // Event listeners
            modal.querySelector('.modal-close')?.addEventListener('click', () => this.close());
            modal.querySelector('.modal-cancel')?.addEventListener('click', () => this.close());
            modal.querySelector('.modal-overlay')?.addEventListener('click', (e) => {
                if (e.target === e.currentTarget && this.options.closeOnOverlay) {
                    this.close();
                }
            });
        }

        open() {
            this.element?.classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        close() {
            this.element?.classList.remove('active');
            document.body.style.overflow = '';
            this.destroy();
        }

        destroy() {
            this.element?.remove();
            this.element = null;
        }

        setContent(content) {
            const body = this.element?.querySelector('.modal-body');
            if (body) {
                body.innerHTML = content;
            }
        }

        onConfirm(callback) {
            this.element?.querySelector('.modal-confirm')?.addEventListener('click', callback);
        }
    }

    /**
     * DataTable Component
     */
    DataTable = class {
        constructor(options = {}) {
            this.options = {
                columns: [],
                data: [],
                perPage: 50,
                searchable: true,
                sortable: true,
                ...options
            };
            this.currentPage = 1;
            this.sortColumn = null;
            this.sortDirection = 'asc';
            this.filteredData = [...this.options.data];
            this.element = null;
            this.create();
        }

        create() {
            const container = document.createElement('div');
            container.className = 'adminoffboard-table-container';
            
            let html = '';
            
            if (this.options.searchable) {
                html += `
                    <div class="table-toolbar">
                        <input type="text" class="table-search" placeholder="Search...">
                        <span class="table-count">${this.filteredData.length} rows</span>
                    </div>
                `;
            }

            html += `<table class="adminoffboard-table"><thead><tr>`;
            this.options.columns.forEach(col => {
                const sortClass = this.sortColumn === col.key ? `sort-${this.sortDirection}` : '';
                html += `<th data-key="${col.key}" class="${sortClass}">${col.label}</th>`;
            });
            html += `</tr></thead><tbody></tbody></table>`;

            if (this.options.pagination !== false) {
                html += `<div class="table-pagination"></div>`;
            }

            container.innerHTML = html;
            this.element = container;

            // Event listeners
            if (this.options.searchable) {
                container.querySelector('.table-search')?.addEventListener('input', (e) => {
                    this.search(e.target.value);
                });
            }

            if (this.options.sortable) {
                container.querySelectorAll('th').forEach(th => {
                    th.addEventListener('click', () => {
                        const key = th.dataset.key;
                        this.sort(key);
                    });
                });
            }

            this.render();
        }

        render() {
            const tbody = this.element?.querySelector('tbody');
            if (!tbody) return;

            const start = (this.currentPage - 1) * this.options.perPage;
            const end = Math.min(start + this.options.perPage, this.filteredData.length);
            const pageData = this.filteredData.slice(start, end);

            tbody.innerHTML = pageData.map(row => {
                return `<tr>${this.options.columns.map(col => {
                    const value = row[col.key] ?? '';
                    return `<td>${col.render ? col.render(value, row) : value}</td>`;
                }).join('')}</tr>`;
            }).join('');

            this.updatePagination();
            this.updateCount();
        }

        search(query) {
            if (!query) {
                this.filteredData = [...this.options.data];
            } else {
                const q = query.toLowerCase();
                this.filteredData = this.options.data.filter(row => {
                    return this.options.columns.some(col => {
                        const value = String(row[col.key] ?? '').toLowerCase();
                        return value.includes(q);
                    });
                });
            }
            this.currentPage = 1;
            this.render();
        }

        sort(key) {
            if (this.sortColumn === key) {
                this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
            } else {
                this.sortColumn = key;
                this.sortDirection = 'asc';
            }

            this.filteredData.sort((a, b) => {
                let aVal = a[key] ?? '';
                let bVal = b[key] ?? '';
                
                if (typeof aVal === 'string') aVal = aVal.toLowerCase();
                if (typeof bVal === 'string') bVal = bVal.toLowerCase();
                
                if (aVal < bVal) return this.sortDirection === 'asc' ? -1 : 1;
                if (aVal > bVal) return this.sortDirection === 'asc' ? 1 : -1;
                return 0;
            });

            // Update sort indicators
            this.element.querySelectorAll('th').forEach(th => {
                th.classList.remove('sort-asc', 'sort-desc');
                if (th.dataset.key === key) {
                    th.classList.add(`sort-${this.sortDirection}`);
                }
            });

            this.render();
        }

        updatePagination() {
            const container = this.element?.querySelector('.table-pagination');
            if (!container) return;

            const totalPages = Math.ceil(this.filteredData.length / this.options.perPage);
            if (totalPages <= 1) {
                container.innerHTML = '';
                return;
            }

            let html = `
                <button class="pagination-prev" ${this.currentPage <= 1 ? 'disabled' : ''}>&laquo;</button>
                <span class="pagination-info">Page ${this.currentPage} of ${totalPages}</span>
                <button class="pagination-next" ${this.currentPage >= totalPages ? 'disabled' : ''}>&raquo;</button>
            `;

            container.innerHTML = html;

            container.querySelector('.pagination-prev')?.addEventListener('click', () => {
                if (this.currentPage > 1) {
                    this.currentPage--;
                    this.render();
                }
            });

            container.querySelector('.pagination-next')?.addEventListener('click', () => {
                if (this.currentPage < totalPages) {
                    this.currentPage++;
                    this.render();
                }
            });
        }

        updateCount() {
            const count = this.element?.querySelector('.table-count');
            if (count) {
                count.textContent = `${this.filteredData.length} rows`;
            }
        }

        setData(data) {
            this.options.data = data;
            this.filteredData = [...data];
            this.currentPage = 1;
            this.render();
        }

        addData(data) {
            this.options.data = [...this.options.data, ...data];
            this.filteredData = [...this.options.data];
            this.render();
        }
    }

    /**
     * Form Component
     */
    Form = class {
        constructor(options = {}) {
            this.options = {
                fields: [],
                submitText: 'Submit',
                validateOnSubmit: true,
                ...options
            };
            this.element = null;
            this.data = {};
            this.create();
        }

        create() {
            const form = document.createElement('form');
            form.className = 'adminoffboard-form';
            form.innerHTML = this.renderFields();
            form.innerHTML += `
                <div class="form-actions">
                    <button type="submit" class="button button-primary">${this.options.submitText}</button>
                    <button type="reset" class="button button-secondary">Reset</button>
                </div>
            `;

            this.element = form;
            this.element.addEventListener('submit', (e) => {
                e.preventDefault();
                if (this.options.validateOnSubmit && !this.validate()) {
                    return;
                }
                this.submit();
            });

            this.element.addEventListener('reset', () => {
                this.reset();
            });
        }

        renderFields() {
            return this.options.fields.map(field => {
                const value = this.data[field.name] ?? field.default ?? '';
                const required = field.required ? 'required' : '';
                const disabled = field.disabled ? 'disabled' : '';

                let input = '';
                switch (field.type) {
                    case 'text':
                    case 'email':
                    case 'password':
                    case 'number':
                        input = `<input type="${field.type}" id="${field.name}" name="${field.name}" value="${value}" placeholder="${field.placeholder || ''}" ${required} ${disabled}>`;
                        break;
                    case 'textarea':
                        input = `<textarea id="${field.name}" name="${field.name}" placeholder="${field.placeholder || ''}" ${required} ${disabled}>${value}</textarea>`;
                        break;
                    case 'select':
                        const options = field.options.map(opt => 
                            `<option value="${opt.value}" ${opt.value === value ? 'selected' : ''}>${opt.label}</option>`
                        ).join('');
                        input = `<select id="${field.name}" name="${field.name}" ${required} ${disabled}>${options}</select>`;
                        break;
                    case 'checkbox':
                        input = `
                            <label class="checkbox-label">
                                <input type="checkbox" id="${field.name}" name="${field.name}" ${value ? 'checked' : ''} ${disabled}>
                                <span class="checkbox-text">${field.label}</span>
                            </label>
                        `;
                        break;
                    case 'hidden':
                        input = `<input type="hidden" id="${field.name}" name="${field.name}" value="${value}">`;
                        break;
                    default:
                        input = `<input type="text" id="${field.name}" name="${field.name}" value="${value}" ${required} ${disabled}>`;
                }

                if (field.type === 'checkbox') {
                    return `<div class="form-group">${input}</div>`;
                }

                return `
                    <div class="form-group">
                        <label for="${field.name}">${field.label} ${field.required ? '*' : ''}</label>
                        ${input}
                        ${field.hint ? `<span class="input-hint">${field.hint}</span>` : ''}
                        ${field.error ? `<span class="input-error">${field.error}</span>` : ''}
                    </div>
                `;
            }).join('');
        }

        validate() {
            let valid = true;
            this.options.fields.forEach(field => {
                if (field.required) {
                    const input = this.element.querySelector(`[name="${field.name}"]`);
                    if (input && !input.value.trim()) {
                        this.showError(field.name, `${field.label} is required`);
                        valid = false;
                    } else {
                        this.clearError(field.name);
                    }
                }
            });
            return valid;
        }

        showError(name, message) {
            const field = this.options.fields.find(f => f.name === name);
            if (field) {
                field.error = message;
                this.rebuild();
            }
        }

        clearError(name) {
            const field = this.options.fields.find(f => f.name === name);
            if (field) {
                field.error = null;
                this.rebuild();
            }
        }

        rebuild() {
            if (this.element) {
                const parent = this.element.parentNode;
                const newForm = this.create();
                if (parent) {
                    parent.replaceChild(newForm.element, this.element);
                    this.element = newForm.element;
                }
            }
        }

        submit() {
            const data = {};
            this.element.querySelectorAll('[name]').forEach(input => {
                if (input.type === 'checkbox') {
                    data[input.name] = input.checked;
                } else if (input.type === 'select') {
                    data[input.name] = input.value;
                } else {
                    data[input.name] = input.value;
                }
            });
            this.options.onSubmit?.(data);
        }

        reset() {
            this.data = {};
            this.rebuild();
        }

        setData(data) {
            this.data = { ...this.data, ...data };
            this.rebuild();
        }

        getData() {
            const data = {};
            this.element.querySelectorAll('[name]').forEach(input => {
                if (input.type === 'checkbox') {
                    data[input.name] = input.checked;
                } else {
                    data[input.name] = input.value;
                }
            });
            return data;
        }
    }

    /**
     * Notification Component
     */
    Notification = class {
        constructor() {
            this.container = null;
            this.create();
        }

        create() {
            this.container = document.createElement('div');
            this.container.className = 'adminoffboard-notifications';
            document.body.appendChild(this.container);
        }

        show(message, type = 'info', duration = 5000) {
            const notification = document.createElement('div');
            notification.className = `notification notification-${type}`;
            notification.innerHTML = `
                <span class="notification-icon">${this.getIcon(type)}</span>
                <span class="notification-message">${message}</span>
                <button class="notification-close">&times;</button>
            `;

            this.container.appendChild(notification);

            notification.querySelector('.notification-close')?.addEventListener('click', () => {
                this.dismiss(notification);
            });

            if (duration > 0) {
                setTimeout(() => {
                    this.dismiss(notification);
                }, duration);
            }
        }

        dismiss(notification) {
            notification.classList.add('notification-dismiss');
            setTimeout(() => {
                notification.remove();
            }, 300);
        }

        getIcon(type) {
            const icons = {
                success: '✓',
                error: '✗',
                warning: '⚠',
                info: 'ℹ'
            };
            return icons[type] || 'ℹ';
        }

        success(message, duration = 3000) {
            this.show(message, 'success', duration);
        }

        error(message, duration = 5000) {
            this.show(message, 'error', duration);
        }

        warning(message, duration = 4000) {
            this.show(message, 'warning', duration);
        }

        info(message, duration = 3000) {
            this.show(message, 'info', duration);
        }
    }

    /**
     * Progress Component
     */
    Progress = class {
        constructor(options = {}) {
            this.options = {
                value: 0,
                max: 100,
                label: '',
                showLabel: true,
                ...options
            };
            this.element = null;
            this.create();
        }

        create() {
            const progress = document.createElement('div');
            progress.className = 'adminoffboard-progress';
            progress.innerHTML = `
                <div class="progress-bar">
                    <div class="progress-fill" style="width: ${this.getPercentage()}%"></div>
                </div>
                ${this.options.showLabel ? `<span class="progress-label">${this.options.label}</span>` : ''}
                <span class="progress-value">${this.options.value}/${this.options.max}</span>
            `;

            this.element = progress;
        }

        setValue(value) {
            this.options.value = Math.min(value, this.options.max);
            this.update();
        }

        setMax(max) {
            this.options.max = max;
            this.update();
        }

        setLabel(label) {
            this.options.label = label;
            this.update();
        }

        getPercentage() {
            return (this.options.value / this.options.max) * 100;
        }

        update() {
            if (!this.element) return;
            const fill = this.element.querySelector('.progress-fill');
            const value = this.element.querySelector('.progress-value');
            const label = this.element.querySelector('.progress-label');

            if (fill) {
                fill.style.width = `${this.getPercentage()}%`;
            }
            if (value) {
                value.textContent = `${this.options.value}/${this.options.max}`;
            }
            if (label && this.options.label) {
                label.textContent = this.options.label;
            }
        }

        render() {
            return this.element;
        }
    }

    /**
     * Tabs Component
     */
    Tabs = class {
        constructor(options = {}) {
            this.options = {
                tabs: [],
                active: 0,
                ...options
            };
            this.element = null;
            this.create();
        }

        create() {
            const container = document.createElement('div');
            container.className = 'adminoffboard-tabs';
            
            const tabList = document.createElement('ul');
            tabList.className = 'tab-list';
            
            this.options.tabs.forEach((tab, index) => {
                const li = document.createElement('li');
                li.className = `tab-item ${index === this.options.active ? 'active' : ''}`;
                li.innerHTML = `<a href="#" data-tab="${index}">${tab.label}</a>`;
                li.addEventListener('click', (e) => {
                    e.preventDefault();
                    this.switchTo(index);
                });
                tabList.appendChild(li);
            });

            const tabContent = document.createElement('div');
            tabContent.className = 'tab-content';
            tabContent.innerHTML = this.options.tabs[this.options.active]?.content || '';

            container.appendChild(tabList);
            container.appendChild(tabContent);
            this.element = container;
        }

        switchTo(index) {
            if (index < 0 || index >= this.options.tabs.length) return;

            this.options.active = index;
            
            // Update tabs
            this.element.querySelectorAll('.tab-item').forEach((el, i) => {
                el.classList.toggle('active', i === index);
            });

            // Update content
            const content = this.element.querySelector('.tab-content');
            if (content) {
                content.innerHTML = this.options.tabs[index]?.content || '';
            }

            this.options.onSwitch?.(index);
        }

        render() {
            return this.element;
        }
    }
}

// Initialize components
document.addEventListener('DOMContentLoaded', () => {
    window.AdminOffboardComponents = new AdminOffboardComponents();
});