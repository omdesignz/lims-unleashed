// resources/js/Services/ResultsDataService.js
export class ResultsDataService {
    static getActionValueField(action) {
        switch (action) {
            case 'verify':
                return 'verified_value';
            case 'approve':
                return 'approved_value';
            default:
                return 'inserted_value';
        }
    }

    static getResultValue(result, action = 'analyze') {
        if (!result) {
            return null;
        }

        const field = this.getActionValueField(action);
        return result[field] ?? null;
    }

    static hasResultValue(value) {
        return value !== null
            && value !== undefined
            && String(value).trim() !== '';
    }

    static isQualitativeResult(result) {
        return Boolean(
            result?.result_is_qualitative
            || result?.parameter_id?.result_is_qualitative
            || result?.result_type === 'qualitative'
            || result?.parameter_id?.result_type === 'qualitative'
        );
    }

    static getQualitativeOptions(result) {
        const options = result?.result_options || result?.parameter_id?.result_options;

        if (Array.isArray(options) && options.length > 0) {
            return options;
        }

        return this.isQualitativeResult(result) ? ['Presença', 'Ausência'] : [];
    }

    static getDisplayFormat(result) {
        return result?.display_format || result?.extra_data?.display_format || 'standard';
    }

    static setDisplayFormat(result, displayFormat) {
        if (!result) {
            return;
        }

        const normalizedFormat = displayFormat === 'scientific' ? 'scientific' : 'standard';
        result.display_format = normalizedFormat;
        result.extra_data = {
            ...(result.extra_data || {}),
            display_format: normalizedFormat,
        };
    }

    static normalizeNumericValue(value) {
        if (!this.hasResultValue(value)) {
            return null;
        }

        const normalized = String(value).trim().replace(',', '.');

        if (normalized === '' || Number.isNaN(Number(normalized))) {
            return null;
        }

        return Number(normalized);
    }

    static formatResultValue(value, result = null) {
        if (!this.hasResultValue(value)) {
            return '';
        }

        if (this.getDisplayFormat(result) !== 'scientific') {
            return value;
        }

        const numericValue = this.normalizeNumericValue(value);

        if (numericValue === null) {
            return value;
        }

        const decimalPlaces = Number(result?.decimal_places ?? result?.parameter_id?.decimal_places ?? 2);
        const precision = Number.isFinite(decimalPlaces) ? Math.min(Math.max(decimalPlaces, 0), 8) : 2;

        return numericValue.toExponential(precision).replace('e', ' × 10^');
    }

    static getDeclaredCalculationParameters(result) {
        return Array.isArray(result?.calculation_parameters)
            ? result.calculation_parameters.filter(Boolean)
            : [];
    }

    static getCalculationVariableNames(result) {
        const formulaVariables = result?.formula?.variables?.map((variable) => variable.name).filter(Boolean) || [];
        const declaredVariables = this.getDeclaredCalculationParameters(result);

        return [...new Set([...
            formulaVariables,
            ...declaredVariables,
        ])];
    }

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
                code: result.parameter_id?.code || result.parameter_code,
                result_options: result.parameter_id?.result_options || result.result_options || [],
                result_is_qualitative: Boolean(result.parameter_id?.result_is_qualitative || result.result_is_qualitative),
            },
            result_is_qualitative: Boolean(result.result_is_qualitative || result.parameter_id?.result_is_qualitative),
            result_options: result.result_options || result.parameter_id?.result_options || [],
            display_format: result.display_format || result.extra_data?.display_format || 'standard',
            extra_data: {
                ...(result.extra_data || {}),
                display_format: result.display_format || result.extra_data?.display_format || 'standard',
            },
            requires_calculation: result.requires_calculation || false,
            calculation_metadata: result.calculation_metadata || null,
            manual_override: result.manual_override || false,
            active: result.active !== undefined ? result.active : true,
            insertion_method: result.insertion_method || 'batch'
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
            const variableNames = this.getCalculationVariableNames(calcParam);
            return variableNames.includes(parameter.parameter_id?.code);
        });
    }

    /**
     * Extract all required input variables from calculated parameters
     */
    static getRequiredInputVariables(calculatedParams) {
        const variables = new Set();
        
        calculatedParams.forEach(param => {
            this.getCalculationVariableNames(param).forEach(variable => variables.add(variable));
        });
        
        return Array.from(variables);
    }

    /**
     * Prepare data for calculation component
     */
    static prepareForCalculation(calculatedParams, allResults, action) {
        const existingData = {};

        existingData['action'] = action || 'analyze';
        
        const inputVariables = this.getRequiredInputVariables(calculatedParams);
        inputVariables.forEach(varName => {
            const param = allResults.find(r => 
                r.parameter_id?.code === varName
            );
            if (param && param.inserted_value !== undefined) {
                existingData[varName] = param.inserted_value;
            }
        });
        
        calculatedParams.forEach(param => {
            const code = param.parameter_id?.code;
            if (code && param.inserted_value !== undefined) {
                existingData[code] = param.inserted_value;
            }
        });
        
        return existingData;
    }

    /**
     * Prepare data for single parameter calculation
     */
    static prepareForSingleCalculation(parameter, allResults) {
        const existingData = {};
        
        const requiredVariables = this.getCalculationVariableNames(parameter);
        
        requiredVariables.forEach(varName => {
            const param = allResults.find(r => 
                r.parameter_id?.code === varName
            );
            if (param && param.inserted_value !== undefined) {
                existingData[varName] = param.inserted_value;
            }
        });
        
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

    static getCalculationReadiness(result, allResults, action = 'analyze') {
        const requiredVariables = this.getCalculationVariableNames(result);

        if (!result?.requires_calculation) {
            return {
                ready: true,
                requiredVariables: [],
                missingVariables: [],
                providedVariables: [],
            };
        }

        const providedVariables = requiredVariables.filter((variable) => {
            const sourceResult = allResults.find((item) => item.parameter_id?.code === variable);
            const value = this.getResultValue(sourceResult, action);

            return value !== null && value !== undefined && String(value).trim() !== '';
        });

        const missingVariables = requiredVariables.filter((variable) => !providedVariables.includes(variable));

        return {
            ready: missingVariables.length === 0,
            requiredVariables,
            missingVariables,
            providedVariables,
        };
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
                        updatedResult.is_calculated = true;
                        break;
                    case 'verify':
                        updatedResult.verified_value = calculatedData[code];
                        updatedResult.uncertainty_value = calculatedData[`${code}_uncertainty_value`] || null;
                        updatedResult.min_ref_value = calculatedData[`${code}_min_ref_value`] || null;
                        updatedResult.max_ref_value = calculatedData[`${code}_max_ref_value`] || null;
                        updatedResult.verification_notes = calculatedData[`${code}_verification_notes`] || null;
                        updatedResult.calculation_metadata = metadata[code] || [];
                        updatedResult.is_calculated = true;
                        break;
                    case 'approve':
                        updatedResult.approved_value = calculatedData[code];
                        updatedResult.uncertainty_value = calculatedData[`${code}_uncertainty_value`] || null;
                        updatedResult.min_ref_value = calculatedData[`${code}_min_ref_value`] || null;
                        updatedResult.max_ref_value = calculatedData[`${code}_max_ref_value`] || null;
                        updatedResult.approval_notes = calculatedData[`${code}_approval_notes`] || null;
                        updatedResult.calculation_metadata = metadata[code] || [];
                        updatedResult.is_calculated = true;
                        break;
                }

                updatedResult.manual_override = overrides[code] || false;
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
            }
            
            return updatedResult;
        });
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
                        updated.is_calculated = resultData.is_calculated || false;
                        updated.insertion_method = 'individual';
                        break;
                    case 'verify':
                        updated.verified_value = resultData.value;
                        updated.calculation_metadata = resultData.metadata || null;
                        updated.manual_override = resultData.override || false;
                        updated.is_calculated = resultData.is_calculated || false;
                        break;
                    case 'approve':
                        updated.approved_value = resultData.value;
                        updated.calculation_metadata = resultData.metadata || null;
                        updated.manual_override = resultData.override || false;
                        updated.is_calculated = resultData.is_calculated || false;
                        break;
                }
                
                return updated;
            }
            
            // Also update input variables if they're in the results array
            if (resultData.inputs && Object.keys(resultData.inputs).includes(result.parameter_id?.code)) {
                switch (action) {
                    case 'analyze':
                        result.inserted_value = resultData.inputs[result.parameter_id.code];
                        break;
                    case 'verify':
                        result.verified_value = resultData.inputs[result.parameter_id.code];
                        break;
                    case 'approve':
                        result.approved_value = resultData.inputs[result.parameter_id.code];
                        break;
                }
            }
            
            return result;
        });
    }

    /**
     * Get parameters pending individual insertion
     */
    static getPendingIndividualParameters(results, action) {
        switch (action) {
            case 'analyze':
                return results.filter(r => !r.inserted_value || r.inserted_value === '');
            case 'verify':
                return results.filter(r => r.inserted_value && (!r.verified_value || r.verified_value === ''));
            case 'approve':
                return results.filter(r => r.verified_value && (!r.approved_value || r.approved_value === ''));
            default:
                return [];
        }
    }

    /**
     * Get completed individual parameters
     */
    static getCompletedIndividualParameters(results, action) {
        switch (action) {
            case 'analyze':
                return results.filter(r => r.inserted_value && r.inserted_value !== '');
            case 'verify':
                return results.filter(r => r.verified_value && r.verified_value !== '');
            case 'approve':
                return results.filter(r => r.approved_value && r.approved_value !== '');
            default:
                return [];
        }
    }

    /**
     * Check if all results are completed for the current action
     */
    static isWorkflowComplete(results, action) {
        const pending = this.getPendingIndividualParameters(results, action);
        return pending.length === 0;
    }
}

export default ResultsDataService;
