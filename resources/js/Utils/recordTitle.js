function readableText(value) {
  if (typeof value === 'number' && Number.isFinite(value)) {
    return String(value);
  }

  if (typeof value !== 'string' || !value.trim() || /^data:/i.test(value.trim())) {
    return null;
  }

  return value.trim();
}

function fieldValue(record, field) {
  if (!field) {
    return undefined;
  }

  if (Object.hasOwn(record, field)) {
    return record[field];
  }

  return field.split('.').reduce((value, key) => value?.[key], record);
}

export function recordTitle(record, columns = [], titleField = '') {
  const explicitTitle = readableText(fieldValue(record, titleField));
  if (explicitTitle !== null) {
    return explicitTitle;
  }

  for (const column of columns) {
    if (column.visible === false || ['qr', 'image', 'actions', 'boolean'].includes(column.type)) {
      continue;
    }

    const title = readableText(fieldValue(record, column.field));
    if (title !== null) {
      return title;
    }
  }

  return `#${readableText(record.id) ?? '—'}`;
}
