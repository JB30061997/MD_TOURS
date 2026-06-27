export function toDateInputValue(value) {
    if (!value) return "";

    const raw = String(value);
    const displayMatch = raw.match(/^(\d{2})\/(\d{2})\/(\d{4})$/);

    if (displayMatch) {
        return `${displayMatch[3]}-${displayMatch[2]}-${displayMatch[1]}`;
    }

    const match = raw.match(/^(\d{4})-(\d{2})-(\d{2})/);

    if (match) {
        return `${match[1]}-${match[2]}-${match[3]}`;
    }

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return "";
    }

    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, "0");
    const day = String(date.getDate()).padStart(2, "0");

    return `${year}-${month}-${day}`;
}

export function formatDate(value) {
    const dateValue = toDateInputValue(value);

    if (!dateValue) {
        return "-";
    }

    const [year, month, day] = dateValue.split("-");

    return `${day}/${month}/${year}`;
}

export function formatDateTime(value) {
    const date = formatDate(value);

    if (date === "-") {
        return date;
    }

    const raw = String(value || "");
    const timeMatch = raw.match(/[T\s](\d{2}):(\d{2})/);

    return timeMatch ? `${date} ${timeMatch[1]}:${timeMatch[2]}` : date;
}

export function formatPeriod(start, end) {
    return `${formatDate(start)} → ${formatDate(end)}`;
}
