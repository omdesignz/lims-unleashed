function option(value) {
  if (!value) {
    return null;
  }

  if (typeof value === "object") {
    return value;
  }

  return { value, label: String(value) };
}

export function createEmptyMatrixProfile() {
  return {
    profile_id: null,
    profile: "",
    price: 0,
  };
}

export function createMatrixProfileFromRecord(profile = {}) {
  const selectedProfile = option(profile.profile_id);

  return {
    ...createEmptyMatrixProfile(),
    ...profile,
    profile_id: selectedProfile,
    profile: profile.profile || selectedProfile?.label || "",
    price: Number(profile.price ?? selectedProfile?.parameters_price ?? selectedProfile?.price ?? 0),
  };
}

export function createEmptyMatrixData() {
  return {
    id: null,
    code: "",
    description: "",
    price: 0,
    fixed_price: 0,
    charge_tax: false,
    withhold_tax: false,
    tax_id: null,
    tax_percentage: 0,
    exemption_id: null,
    exemption_code: null,
    profiles: [],
  };
}

export function createMatrixDataFromRecord(record = {}) {
  return {
    ...createEmptyMatrixData(),
    id: record.id || null,
    code: record.code || "",
    description: record.description || "",
    price: Number(record.price || 0),
    fixed_price: Number(record.fixed_price || 0),
    charge_tax: Boolean(record.charge_tax),
    withhold_tax: Boolean(record.withhold_tax),
    tax_id: option(record.tax_id),
    tax_percentage: Number(record.tax_percentage || record.tax_id?.percent || 0),
    exemption_id: option(record.exemption_id),
    exemption_code: record.exemption_code || record.exemption_id?.label || null,
    profiles: (record.profiles || []).map(createMatrixProfileFromRecord),
  };
}
