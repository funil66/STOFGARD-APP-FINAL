<style>
    /* Estilização Premium do Calendário */
    .fc-theme-standard .fc-scrollgrid {
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 12px;
        overflow: hidden;
    }

    .fc-col-header-cell {
        background-color: #f8fafc;
        padding: 12px 0;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        font-size: 0.75rem;
        color: #64748b;
    }

    .dark .fc-col-header-cell {
        background-color: #1e293b;
        color: #94a3b8;
    }

    /* Eventos */
    .fc-event {
        border: none !important;
        border-radius: 6px !important;
        padding: 2px 4px !important;
        font-size: 0.8rem !important;
        font-weight: 500 !important;
        box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
        transition: all 0.2s;
        cursor: pointer;
        margin-bottom: 2px !important;
    }

    .fc-event:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        z-index: 10;
        filter: brightness(1.1);
    }

    .fc-daygrid-event-dot {
        border-width: 4px !important;
    }

    .fc-event-time {
        font-weight: 700 !important;
        margin-right: 4px;
        opacity: 0.9;
    }

    .fc-event-title {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    /* Dias */
    .fc-daygrid-day-number {
        font-size: 0.9rem;
        font-weight: 600;
        padding: 8px !important;
        color: #475569;
    }

    .dark .fc-daygrid-day-number {
        color: #cbd5e1;
    }

    .fc-day-today {
        background-color: rgba(37, 99, 235, 0.05) !important;
    }

    .dark .fc-day-today {
        background-color: rgba(37, 99, 235, 0.1) !important;
    }

    /* Botões */
    .fc-button-primary {
        background-color: #2563eb !important;
        border-color: #2563eb !important;
        text-transform: capitalize;
        font-weight: 500;
        padding: 6px 16px !important;
        border-radius: 8px !important;
    }

    .fc-button-primary:hover {
        background-color: #1d4ed8 !important;
        border-color: #1d4ed8 !important;
    }

    .fc-toolbar-title {
        font-size: 1.25rem !important;
        font-weight: 700 !important;
        color: #1e293b;
    }

    .dark .fc-toolbar-title {
        color: #f1f5f9;
    }

    /* Popover (More events) */
    .fc-popover {
        border-radius: 12px !important;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1) !important;
        border: none !important;
        z-index: 9999;
    }

    .dark .fc-popover {
        background-color: #1e293b !important;
    }

    .fc-popover-header {
        background-color: #f1f5f9 !important;
        border-bottom: 1px solid #e2e8f0;
        border-radius: 12px 12px 0 0 !important;
        padding: 8px 12px !important;
        font-weight: 600;
    }

    .dark .fc-popover-header {
        background-color: #334155 !important;
        border-bottom-color: #475569;
        color: #fff;
    }
</style>