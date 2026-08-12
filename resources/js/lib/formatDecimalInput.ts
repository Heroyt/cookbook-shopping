export const formatDecimalInput = (
    value: string | number | null | undefined,
): string => {
    if (value === null || value === undefined || value === '') {
        return '';
    }

    const decimal = String(value);
    const match = decimal.match(/^(-?\d+)\.(\d+)$/);

    if (match === null) {
        return decimal;
    }

    const fraction = match[2]?.replace(/0+$/, '') ?? '';

    return fraction === '' ? (match[1] ?? decimal) : `${match[1]}.${fraction}`;
};
