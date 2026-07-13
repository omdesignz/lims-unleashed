function normalizeCalculationParameters(value) {
  if (Array.isArray(value)) {
    return value.map((parameter) => String(parameter).trim()).filter(Boolean);
  }

  if (typeof value === "string" && value.trim()) {
    try {
      const parsed = JSON.parse(value);
      return Array.isArray(parsed) ? parsed.map((parameter) => String(parameter).trim()).filter(Boolean) : [];
    } catch {
      return [];
    }
  }

  return [];
}

export function createEmptyParameterData() {
  return {
    name: "",
    code: "",
    price: 0,
    description: "",
    exemption_id: null,
    tax_id: null,
    tax_percentage: 0,
    charge_tax: true,
    withhold_tax: false,
    active: true,
    optimal_analysis_time: "",
    result_is_qualitative: false,
    requires_calculation: false,
    formula_id: null,
    formula_expression: "",
    calculation_parameters: [],
    decimal_places: 4,
    result_type: "quantitative",
    id: null,
  };
}

export function createParameterDataFromRecord(record = {}) {
  return {
    name: record.name || "",
    code: record.code || "",
    price: Number(record.price || 0),
    description: record.description || "",
    exemption_id: record.exemption_id?.id
      ? { value: record.exemption_id.id, label: record.exemption || record.exemption_id.code }
      : null,
    tax_id: record.tax_id?.id
      ? {
          value: record.tax_id.id,
          label: record.tax_id.name || record.tax,
          percent: Number(record.tax_percentage || record.tax_id.percent || 0),
        }
      : null,
    tax_percentage: Number(record.tax_percentage || 0),
    charge_tax: Boolean(record.charge_tax),
    withhold_tax: Boolean(record.withhold_tax),
    active: Boolean(record.active),
    optimal_analysis_time: record.optimal_analysis_time || "",
    result_is_qualitative: Boolean(record.result_is_qualitative),
    requires_calculation: Boolean(record.requires_calculation),
    formula_id: record.formula_id || null,
    formula_expression: record.formula_expression || "",
    calculation_parameters: normalizeCalculationParameters(record.calculation_parameters),
    decimal_places: Number.isInteger(Number(record.decimal_places)) ? Number(record.decimal_places) : 4,
    result_type: record.result_type || (record.result_is_qualitative ? "qualitative" : "quantitative"),
    id: record.id || null,
  };
}
