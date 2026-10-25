/**
 * Format a date as YYYY-MM-DD in the user's local timezone, for date inputs and API params.
 * (toISOString() uses UTC, which gives the wrong day near midnight in non-UTC timezones.)
 */
export function toDateInput(date = new Date()) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}
