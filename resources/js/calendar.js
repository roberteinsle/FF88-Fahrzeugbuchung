import { Calendar } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import listPlugin from '@fullcalendar/list';
import interactionPlugin from '@fullcalendar/interaction';
import deLocale from '@fullcalendar/core/locales/de';

let calendar = null;

function buildEventsUrl(vehicleIds) {
    const base = document.getElementById('booking-calendar')?.dataset?.eventsUrl ?? '/bookings/events';
    const url = new URL(base, window.location.origin);
    if (vehicleIds && vehicleIds.length > 0) {
        vehicleIds.forEach(id => url.searchParams.append('vehicles[]', id));
    }
    return url.toString();
}

function initCalendar() {
    const el = document.getElementById('booking-calendar');
    if (!el || calendar) return;

    const isMobile = window.innerWidth < 430;

    calendar = new Calendar(el, {
        plugins: [dayGridPlugin, timeGridPlugin, listPlugin, interactionPlugin],
        locale: deLocale,
        timeZone: 'Europe/Berlin',
        initialView: isMobile ? 'listWeek' : 'dayGridMonth',
        firstDay: 1,
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,listWeek',
        },
        buttonText: {
            today: 'Heute',
            month: 'Monat',
            week: 'Woche',
            list: 'Liste',
        },
        scrollTime: '06:00:00',
        slotMinTime: '00:00:00',
        slotMaxTime: '24:00:00',
        allDaySlot: false,
        height: 'auto',
        views: isMobile ? {
            // Phones: shorter title, e.g. "28. Sept. – 4. Okt."
            timeGridWeek: {
                titleFormat: { month: 'short', day: 'numeric' },
                // "M, 28." instead of "Mo. 28.9." so seven columns fit
                dayHeaderFormat: { weekday: 'narrow', day: 'numeric' },
            },
            listWeek: { titleFormat: { month: 'short', day: 'numeric' } },
        } : {},
        events: buildEventsUrl([]),

        // Month view on mobile: show colored dots, no text
        eventContent: function(arg) {
            if (arg.view.type === 'dayGridMonth' && isMobile) {
                return {
                    html: `<span class="fc-event-dot" style="background-color:${arg.event.backgroundColor}"></span>`,
                };
            }
            return true; // default rendering
        },

        eventClick: function(info) {
            const props = info.event.extendedProps;
            window.Livewire?.dispatch('booking-detail-open', { bookingId: props.bookingId });
        },

        dateClick: function(info) {
            if (calendar.view.type === 'dayGridMonth' && isMobile) {
                // On mobile month view, show day list instead of opening form
                window.Livewire?.dispatch('day-list-open', { date: info.dateStr });
            } else {
                window.Livewire?.dispatch('booking-form-open', {
                    date: info.dateStr,
                    vehicleId: null,
                });
            }
        },

        viewDidMount: function(arg) {
            // Month/list grow with their content; the week grid scrolls inside a fixed height
            // (height is not a view-specific option in FullCalendar)
            const height = arg.view.type === 'timeGridWeek' ? 650 : 'auto';
            // Deferred: options can't be changed while FullCalendar is still rendering the view
            setTimeout(() => {
                if (calendar && calendar.getOption('height') !== height) {
                    calendar.setOption('height', height);
                    if (height !== 'auto') calendar.scrollToTime('06:00:00');
                }
            });
            window.Livewire?.dispatch('calendar-view-changed', { view: arg.view.type });
        },
    });

    calendar.render();
}

// Initialize when DOM is ready after Livewire boots
document.addEventListener('livewire:init', () => {
    // Small delay to ensure the wire:ignore div is rendered
    requestAnimationFrame(initCalendar);

    window.Livewire.on('calendar-filter-changed', ({ vehicleIds }) => {
        if (calendar) {
            calendar.setOption('events', buildEventsUrl(vehicleIds));
            calendar.refetchEvents();
        }
    });

    window.Livewire.on('calendar-refresh', () => {
        if (calendar) {
            calendar.refetchEvents();
        }
    });
});

// Re-init if the calendar div appears (e.g., after navigation)
document.addEventListener('livewire:navigated', () => {
    calendar = null;
    requestAnimationFrame(initCalendar);
});
