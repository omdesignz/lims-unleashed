export class ResultsDataService {
    /**
     * Normalize results data from API
     */
    static normalizeResults(apiResults) {
        return apiResults.map(result => ({
            ...result,
            result_id: result?.result_id,
            cfu1: result.cfu1 || null,
            cfu2: result.cfu2 || null,
            d1: result.d1 || null,
            d2: result.d2 || null,
            verification_status: result.verification_status || null,
            verification_notes: result.verification_notes || null,
            insertion_notes: result.insertion_notes || null,
            approval_notes: result.approval_notes || null,
            volume: result.volume || 1,
            parameter_id: { 
                ...result.parameter_id, 
                code: result.parameter_id?.code || result.parameter_code
            },
            // Ensure calculation fields exist 
            requires_calculation: result.requires_calculation || false,
            calculation_metadata: result.calculation_metadata || null,
            manual_override: result.manual_override || false,
            active: result.active !== undefined ? result.active : true
        }));
    }

    /**
     * Separate calculated parameters from input variables
     */
    static separateCalculatedParameters(results) {
        const calculatedParams = results.filter(p => 
            p.requires_calculation && p.active
        );
        
        const inputVariables = results.filter(p => 
            !p.requires_calculation && 
            p.active && 
            // Check if this parameter is used as input for calculations
            this.isInputVariable(p, calculatedParams)
        );
        
        const manualParams = results.filter(p => 
            !p.requires_calculation && 
            p.active &&
            !this.isInputVariable(p, calculatedParams)
        );
        
        return { calculatedParams, inputVariables, manualParams };
    }

    /**
     * Check if a parameter is used as input for calculations
     */
    static isInputVariable(parameter, calculatedParams) {
        return calculatedParams.some(calcParam => {
            const variableNames = calcParam.formula?.variables?.map(v => v.name) || [];
            return variableNames.includes(parameter.parameter_id?.code);
        });
    }

    /**
     * Extract all required input variables from calculated parameters
     */
    static getRequiredInputVariables(calculatedParams) {
        const variables = new Set();
        
        calculatedParams.forEach(param => {
            param.formula?.variables?.forEach(variable => {
                variables.add(variable.name);
            });
        });
        
        return Array.from(variables);
    }

    /**
     * Prepare data for calculation component
     */
    static prepareForCalculation(calculatedParams, allResults, action) {
        const existingData = {};

        existingData['action'] = action || 'really';
        
        // First, collect input variables from results
        const inputVariables = this.getRequiredInputVariables(calculatedParams);
        inputVariables.forEach(varName => {
            // Find the parameter with this code
            const param = allResults.find(r => 
                r.parameter_id?.code === varName
            );
            if (param && param.inserted_value !== undefined) {
                existingData[varName] = param.inserted_value;
            }
        });
        
        // Then, collect existing calculated values
        calculatedParams.forEach(param => {
            const code = param.parameter_id?.code;
            if (code && param.inserted_value !== undefined) {
                existingData[code] = param.inserted_value;
            }
        });
        
        return existingData;
    }

    /**
     * Merge calculation results back into main results array
     */
    static mergeCalculationResults(existingResults, calculationPayload, action) {
        const { results: calculatedData, overrides, metadata } = calculationPayload;
        
        return existingResults.map(result => {
            const code = result.parameter_id?.code;
            if (!code) return result;
            
            const updatedResult = { ...result };
            
            // Update calculated parameters
            if (result.requires_calculation && calculatedData[code]) {
                switch (action) {
                    case 'analyze':
                        updatedResult.inserted_value = calculatedData[code];
                        updatedResult.uncertainty_value = calculatedData[`${code}_uncertainty_value`] || null;
                        updatedResult.min_ref_value = calculatedData[`${code}_min_ref_value`] || null;
                        updatedResult.max_ref_value = calculatedData[`${code}_max_ref_value`] || null;
                        updatedResult.insertion_notes = calculatedData[`${code}_insertion_notes`] || null;
                        updatedResult.calculation_metadata = metadata[code] || [];
                        break;
                    case 'verify':
                        updatedResult.verified_value = calculatedData[code];
                        updatedResult.uncertainty_value = calculatedData[`${code}_uncertainty_value`] || null;
                        updatedResult.min_ref_value = calculatedData[`${code}_min_ref_value`] || null;
                        updatedResult.max_ref_value = calculatedData[`${code}_max_ref_value`] || null;
                        updatedResult.verification_notes = calculatedData[`${code}_verification_notes`] || null;
                        updatedResult.calculation_metadata = metadata[code] || [];
                        break;
                    case 'approve':
                        updatedResult.approved_value = calculatedData[code];
                        updatedResult.uncertainty_value = calculatedData[`${code}_uncertainty_value`] || null;
                        updatedResult.min_ref_value = calculatedData[`${code}_min_ref_value`] || null;
                        updatedResult.max_ref_value = calculatedData[`${code}_max_ref_value`] || null;
                        updatedResult.approval_notes = calculatedData[`${code}_approval_notes`] || null;
                        updatedResult.calculation_metadata = metadata[code] || [];
                        break;
                }

                // updatedResult.inserted_value = calculatedData[code];
                // updatedResult.calculation_metadata = metadata[code] || null;
                // updatedResult.manual_override = overrides[code] || false;
            }
            
            // Update input variables
            if (calculatedData[code] && !result.requires_calculation) {
                switch (action) {
                    case 'analyze':
                        updatedResult.inserted_value = calculatedData[code];
                        break;
                    case 'verify':
                        updatedResult.verified_value = calculatedData[code];
                        break;
                    case 'approve':
                        updatedResult.approved_value = calculatedData[code];
                        break;
                }

                // updatedResult.inserted_value = calculatedData[code];
            }
            
            return updatedResult;
        });
    }


    /**
     * Prepare data for single parameter calculation
     */
    static prepareForSingleCalculation(parameter, allResults) {
    const existingData = {};
    
    // Get required variables from the formula
    const requiredVariables = parameter.formula?.variables?.map(v => v.name) || [];
    
    // Collect input values
    requiredVariables.forEach(varName => {
        const param = allResults.find(r => 
        r.parameter_id?.code === varName
        );
        if (param && param.inserted_value !== undefined) {
        existingData[varName] = param.inserted_value;
        }
    });
    
    // Add existing calculated value if any
    const code = parameter.parameter_id?.code;
    if (code) {
        const existingParam = allResults.find(r => 
        r.parameter_id?.code === code
        );
        if (existingParam && existingParam.inserted_value) {
        existingData[code] = existingParam.inserted_value;
        }
    }
    
    return existingData;
    }

    /**
     * Merge single calculation result
     */
    static mergeSingleCalculationResult(existingResults, parameterCode, resultData, action) {
    return existingResults.map(result => {
        if (result.parameter_id?.code === parameterCode) {
        const updated = { ...result };
        
        switch (action) {
            case 'analyze':
            updated.inserted_value = resultData.value;
            updated.calculation_metadata = resultData.metadata || null;
            updated.manual_override = resultData.override || false;
            break;
            case 'verify':
            updated.verified_value = resultData.value;
            updated.calculation_metadata = resultData.metadata || null;
            updated.manual_override = resultData.override || false;
            break;
            case 'approve':
            updated.approved_value = resultData.value;
            updated.calculation_metadata = resultData.metadata || null;
            updated.manual_override = resultData.override || false;
            break;
        }
        
        return updated;
        }
        
        // Also update input variables if they're in the results array
        if (resultData.inputs && Object.keys(resultData.inputs).includes(result.parameter_id?.code)) {
        switch (action) {
            case 'analyze':
            updated.inserted_value = resultData.inputs[result.parameter_id.code];
            break;
            case 'verify':
            updated.verified_value = resultData.inputs[result.parameter_id.code];
            break;
            case 'approve':
            updated.approved_value = resultData.inputs[result.parameter_id.code];
            break;
        }
        }
        
        return result;
    });
    }

}
