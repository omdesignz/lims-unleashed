function option(value) {
  if (!value) {
    return null;
  }

  if (typeof value === "object") {
    return value;
  }

  return { value, label: String(value) };
}

function normalizeDilutions(value) {
  if (Array.isArray(value)) {
    return value;
  }

  if (typeof value === "string" && value.trim()) {
    try {
      const parsed = JSON.parse(value);
      return Array.isArray(parsed) ? parsed : [];
    } catch {
      return [];
    }
  }

  return [];
}

function normalizeTextCollection(value) {
  if (Array.isArray(value)) {
    return value.filter(Boolean).join(", ");
  }

  if (value && typeof value === "object") {
    return Object.values(value).filter(Boolean).join(", ");
  }

  if (typeof value === "string" && value.trim()) {
    try {
      return normalizeTextCollection(JSON.parse(value));
    } catch {
      return value;
    }
  }

  return "";
}

export function createEmptyProfileParameter() {
  return {
    parameter_id: null,
    unit_id: null,
    protocol_id: null,
    standard_id: null,
    nwp_id: null,
    category_id: null,
    formula_id: null,
    count: true,
    min_ref_value: "",
    max_ref_value: "",
    ref_val_origin: "",
    accredited: false,
    subcontractor: "",
    uncertainty_coverage_factor: "",
    dilutions: "",
    extra_data: { dilutions: [] },
    optimal_analysis_time: "",
    price: 0,
  };
}

export function createProfileParameterFromRecord(parameter = {}) {
  return {
    ...createEmptyProfileParameter(),
    ...parameter,
    parameter_id: option(parameter.parameter_id),
    unit_id: option(parameter.unit_id),
    protocol_id: option(parameter.protocol_id),
    standard_id: option(parameter.standard_id),
    nwp_id: option(parameter.nwp_id),
    category_id: option(parameter.category_id),
    formula_id: option(parameter.formula_id),
    count: parameter.count !== false,
    dilutions: normalizeTextCollection(parameter.dilutions),
    extra_data: {
      ...(parameter.extra_data || {}),
      dilutions: normalizeDilutions(parameter.extra_data?.dilutions),
    },
    price: Number(parameter.price || 0),
  };
}

export function createEmptyProfileData() {
  return {
    id: null,
    name: "",
    code: "",
    description: "",
    price: 0,
    category_id: null,
    parameters: [],
  };
}

export function createProfileDataFromRecord(record = {}) {
  return {
    ...createEmptyProfileData(),
    id: record.id || null,
    name: record.name || "",
    code: record.code || "",
    description: record.description || "",
    price: Number(record.price || 0),
    category_id: option(record.category_id),
    parameters: (record.parameters || []).map(createProfileParameterFromRecord),
  };
}
