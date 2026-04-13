<?php
/**
 * Ethiopian Date Picker — Reusable Alpine.js Component Partial
 *
 * Usage in any view:
 *   <?php partial('ethiopian_datepicker'); ?>
 *
 * Then use the component in HTML:
 *   <div x-data="ecDatePicker({ value: '01/06/2018', name: 'hire_date_ec', required: true })">
 *       <template x-ref="picker"></template>
 *   </div>
 *
 * Or use the helper function directly in a form:
 *   ecDateInput('hire_date_ec', 'Hire Date (EC)', $value, true)
 */
?>
<style>
.ec-datepicker { display: flex; gap: 6px; align-items: center; }
.ec-datepicker input, .ec-datepicker select {
    padding: 0.5rem 0.75rem;
    border: 1px solid #d1d5db;
    border-radius: 0.5rem;
    font-size: 0.875rem;
    background: white;
}
html.dark .ec-datepicker input, html.dark .ec-datepicker select {
    background: #1e293b !important;
    color: #e2e8f0 !important;
    border-color: #475569 !important;
}
.ec-datepicker input[type="number"] { width: 72px; }
.ec-datepicker select { min-width: 120px; }
.ec-datepicker .ec-label { font-size: 0.75rem; font-weight: 600; color: #6b7280; }
html.dark .ec-datepicker .ec-label { color: #94a3b8; }
</style>

<script>
/**
 * Ethiopian Calendar Date Picker — Alpine.js component
 *
 * @param {string} value   - Initial value in DD/MM/YYYY format
 * @param {string} name    - Hidden input name for form submission
 * @param {boolean} required - Whether field is required
 */
function ecDatePicker(config) {
    return {
        day: '',
        month: '',
        year: '',
        _name: config.name || 'date_ec',
        _required: config.required || false,
        label: config.label || '',

        months: [
            {num: 1,  name: 'Meskerem'},
            {num: 2,  name: 'Tikimt'},
            {num: 3,  name: 'Hidar'},
            {num: 4,  name: 'Tahsas'},
            {num: 5,  name: 'Tir'},
            {num: 6,  name: 'Yekatit'},
            {num: 7,  name: 'Megabit'},
            {num: 8,  name: 'Miazia'},
            {num: 9,  name: 'Ginbot'},
            {num: 10, name: 'Sene'},
            {num: 11, name: 'Hamle'},
            {num: 12, name: 'Nehase'},
            {num: 13, name: 'Pagume'}
        ],

        init() {
            if (config.value) {
                var parts = config.value.split('/');
                if (parts.length === 3) {
                    this.day = String(parseInt(parts[0]) || '');
                    this.month = String(parseInt(parts[1]) || '');
                    this.year = String(parseInt(parts[2]) || '');
                }
            }
        },

        get fieldName() {
            return this._name;
        },

        get isRequired() {
            return this._required;
        },

        get maxDay() {
            var m = parseInt(this.month);
            if (m === 13) {
                return this.isLeapYear(parseInt(this.year)) ? 6 : 5;
            }
            return 30;
        },

        get daysInMonth() {
            var arr = [];
            for (var i = 1; i <= this.maxDay; i++) { arr.push(i); }
            return arr;
        },

        isLeapYear(y) {
            return y && (y % 4 === 3);
        },

        get formatted() {
            if (this.day && this.month && this.year && String(this.year).length >= 4) {
                var d = String(this.day).padStart(2, '0');
                var m = String(this.month).padStart(2, '0');
                return d + '/' + m + '/' + this.year;
            }
            return '';
        },

        updateValue() {
            // Clamp day if it exceeds the current month's max
            if (this.day && parseInt(this.day) > this.maxDay) {
                this.day = String(this.maxDay);
            }
        }
    };
}
</script>
