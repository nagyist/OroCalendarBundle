define(function(require) {
    'use strict';

    var RecurrenceYearlyView;
    var _ = require('underscore');
    var RecurrenceMonthlyView = require('orocalendar/js/calendar/event/recurrence/recurrence-monthly-view');

    RecurrenceYearlyView = RecurrenceMonthlyView.extend(/** @exports RecurrenceYearlyView.prototype */{
        template: require('tpl!orocalendar/templates/calendar/event/recurrence/recurrence-yearly.html'),
        relatedFields: ['recurrenceType', 'interval', 'instance', 'dayOfWeek', 'dayOfMonth', 'monthOfYear'],

        getTemplateData: function() {
            var self = this;
            var data = RecurrenceYearlyView.__super__.getTemplateData.apply(this, arguments);
            if (data.interval && data.interval >= 12) {
                data.interval /= 12;
            }
            data.monthsOptions = _.map(this.model.RECURRENCE_MONTHS, function(item, key) {
                return {
                    'value': key,
                    'text': item,
                    'selected': Number(key) === Number(self.model.get('monthOfYear'))
                };
            });
            return data;
        },

        onModelChange: function(model) {
            var dayOfMonth = !this.model.get('instance') ? Number(this.model.get('dayOfMonth')) : null;
            var monthOfYear = Number(this.model.get('monthOfYear'));
            var daysInMonth = this._daysInMonth(monthOfYear);
            var $dayOfMonthField = this.$('[data-related-field="dayOfMonth"]');
            if ('monthOfYear' in model.changed) {
                var dayValidationRules = $dayOfMonthField.data('validation');
                dayValidationRules.Number.max = daysInMonth;
                if ($dayOfMonthField.val()) {
                    $dayOfMonthField.trigger('blur');
                }
            }
            if (dayOfMonth === 29 && monthOfYear === 2) { // the 29 of february was selected
                this.setFewerDaysWarning(dayOfMonth);
            } else {
                this.setFewerDaysWarning(false);
            }
        },

        _daysInMonth: function(month) {
            var fullYear = new Date().getFullYear();
            return new Date(fullYear, month, 0).getDate();
        },

        getValue: function() {
            var value = RecurrenceYearlyView.__super__.getValue.apply(this, arguments);
            if (value.interval) {
                value.interval *= 12;
            }
            return value;
        }
    });

    return RecurrenceYearlyView;
});
