<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Permission as ModelsPermission;

class Permission extends ModelsPermission
{
    use HasFactory;

    public CONST MENU_NAME = 'permissions';

    protected $fillable = [
        'name',
        'label',
        'guard_name',
    ];

    public static function defaultPermissions()
    {
        return [
            'view_users',
            'add_users',
            'edit_users',
            'delete_users',

            'view_settings',
            'add_settings',
            'edit_settings',
            'delete_settings',

            'view_roles',
            'add_roles',
            'edit_roles',
            'delete_roles',

            'view_departments',
            'add_departments',
            'edit_departments',
            'delete_departments',

            'view_samples',
            'add_samples',
            'edit_samples',
            'delete_samples',

            'view_matrixes',
            'add_matrixes',
            'edit_matrixes',
            'delete_matrixes',

            'view_profiles',
            'add_profiles',
            'edit_profiles',
            'delete_profiles',

            'view_collections',
            'add_collections',
            'edit_collections',
            'delete_collections',

            'view_customers',
            'add_customers',
            'edit_customers',
            'delete_customers',

            'view_warehouses',
            'add_warehouses',
            'edit_warehouses',
            'delete_warehouses',

            'view_invoices',
            'add_invoices',
            'edit_invoices',
            'delete_invoices',

            'view_products',
            'add_products',
            'edit_products',
            'delete_products',

            'view_results',
            'add_results',
            'edit_results',
            'delete_results',

            'view_analysis',
            'add_analysis',
            'edit_analysis',
            'delete_analysis',

            'view_counteranalysis',
            'add_counteranalysis',
            'edit_counteranalysis',
            'delete_counteranalysis',

            'view_plists',
            'add_plists',
            'edit_plists',
            'delete_plists',

            'view_protocols',
            'add_protocols',
            'edit_protocols',
            'delete_protocols',

            'view_receipts',
            'add_receipts',
            'edit_receipts',
            'delete_receipts',

            'view_emails',
            'add_emails',
            'edit_emails',
            'delete_emails',

            'view_parameters',
            'add_parameters',
            'edit_parameters',
            'delete_parameters',

            'view_standards',
            'add_standards',
            'edit_standards',
            'delete_standards',

            'view_units',
            'add_units',
            'edit_units',
            'delete_units',

            'view_temperatures',
            'add_temperatures',
            'edit_temperatures',
            'delete_temperatures',

            'view_customercats',
            'add_customercats',
            'edit_customercats',
            'delete_customercats',

            'view_atypes',
            'add_atypes',
            'edit_atypes',
            'delete_atypes',

            'view_collendresults',
            'add_collendresults',
            'edit_collendresults',
            'delete_collendresults',

            'view_collabs',
            'add_collabs',
            'edit_collabs',
            'delete_collabs',

            'view_telnumbers',
            'add_telnumbers',
            'edit_telnumbers',
            'delete_telnumbers',

            'view_dcollections',
            'add_dcollections',
            'edit_dcollections',
            'delete_dcollections',

            'view_pcollections',
            'add_pcollections',
            'edit_pcollections',
            'delete_pcollections',

            'view_qcollections',
            'add_qcollections',
            'edit_qcollections',
            'delete_qcollections',
            
            'view_transtypes',
            'add_transtypes',
            'edit_transtypes',
            'delete_transtypes',

            'view_quacertificates',
            'add_quacertificates',
            'edit_quacertificates',
            'delete_quacertificates',

            'view_impcertificates',
            'add_impcertificates',
            'edit_impcertificates',
            'delete_impcertificates',

            'view_expcertificates',
            'add_expcertificates',
            'edit_expcertificates',
            'delete_expcertificates',

            'view_cnotes',
            'add_cnotes',
            'edit_cnotes',
            'delete_cnotes',

            'view_dnotes',
            'add_dnotes',
            'edit_dnotes',
            'delete_dnotes',

            'view_ptypes',
            'add_ptypes',
            'edit_ptypes',
            'delete_ptypes',

            'view_rtypes',
            'add_rtypes',
            'edit_rtypes',
            'delete_rtypes',

            'view_rcollections',
            'add_rcollections',
            'edit_rcollections',
            'delete_rcollections',

            'view_invoicetypes',
            'add_invoicetypes',
            'edit_invoicetypes',
            'delete_invoicetypes',

            'view_pnts',
            'add_pnts',
            'edit_pnts',
            'delete_pnts',

            'view_cguides',
            'add_cguides',
            'edit_cguides',
            'delete_cguides',

            'apply_discounts',
      
        ];
    }
}
