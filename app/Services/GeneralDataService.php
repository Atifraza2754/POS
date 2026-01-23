<?php

namespace App\Services;

class GeneralDataService{

    private $data;

    public function __construct()
    {
    	//
    }

    function getStaffStatus() : array{
        return [
                [
                    'id'    =>  'Pending',
                    'name'    =>  'Pending',
                ],
                [
                    'id'    =>  'Accepted',
                    'name'    =>  'Accepted',
                ],
                [
                    'id'    =>  'Rejected',
                    'name'    =>  'Rejected',
                ],
                [
                    'id'    =>  'Processing',
                    'name'    =>  'Processing',
                ],
                [
                    'id'    =>  'Completed',
                    'name'    =>  'Completed',
                ],
        ];
     }

     /**
     * Helper for replacement of keywords
     * */
    function replaceTemplateKeywords($template, array $replacements)
    {
        $cleanedTemplate = $template;
        foreach ($replacements as $keyword => $value) {
            //$cleanedTemplate = str_replace(':'.$keyword, $value, $cleanedTemplate);
            $cleanedTemplate = str_replace($keyword, $value, $cleanedTemplate);
        }
        return $cleanedTemplate;
    }

    /**
     * Record Batch Tracking Row Count
     * */
    public function getBatchTranckingRowCount(){
        $companySettings = app('company');
        $trackableFields = [
                                'enable_batch_tracking', 
                                'enable_mfg_date', 
                                'enable_exp_date',
                                'enable_model', 
                                //'show_mrp', 
                                'enable_color', 
                                'enable_size'
                            ];
        $batchTrackingRowCount = array_sum(array_map(function ($field) use ($companySettings) {
                                  return (isset($companySettings[$field]) && $companySettings[$field] ==1) ? 1 : 0;
                                }, $trackableFields));

        return $batchTrackingRowCount;
    }
}
