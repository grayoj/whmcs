<?php
/*
 **********************************************************************
 *         Additional Domain Fields (aka Extended Attributes)         *
 **********************************************************************
 *                                                                    *
 * This file contains the default additional domain field definitions *
 * for WHMCS.                                                         *
 *                                                                    *
 * We do not recommend editing this file directly. To customise the   *
 * fields, you should create an overrides file.                       *
 *                                                                    *
 * For more information please refer to the online documentation at   *
 *   https://docs.whmcs.com/Additional_Domain_Fields                   *
 *                                                                    *
 **********************************************************************
 */

// .ma

$ma_type = array (
    'ORG|Organization',
    'IND|Individual'
);

$additionaldomainfields['.ma'][] = array('Name' => 'Type', 'Type' => 'dropdown', 'Options' => implode(',', $as_type), 'Required' => true);
$additionaldomainfields['.ma'][] = array('Name' => 'NID', 'DisplayName' => 'NationalID', 'Type' => 'text', 'Size'     => '55');
$additionaldomainfields['.ma'][] = array('Name' => 'TID', 'DisplayName' => 'Taxpayer Number', 'Type' => 'text', 'Size'     => '55');

// OTE.ma

$otema_type = array (
    'ORG|Organization',
    'IND|Individual'
);

$additionaldomainfields['.ote.ma'][] = array('Name' => 'Type', 'Type' => 'dropdown', 'Options' => implode(',', $otema_type), 'Required' => true);
$additionaldomainfields['.ote.ma'][] = array('Name' => 'NID','LangVar' => 'otema_id', 'DisplayName' => 'NationalID', 'Type' => 'text', 'Size' => '55','Default' => '');
$additionaldomainfields['.ote.ma'][] = array('Name' => 'TID','LangVar' => 'otema_id' , 'DisplayName' => 'Taxpayer Number', 'Type' => 'text', 'Size' => '55','Default' => '');

