<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v12/common/asset_usage.proto

namespace GPBMetadata\Google\Ads\GoogleAds\V12\Common;

class AssetUsage
{
    public static $is_initialized = false;

    public static function initOnce() {
        $pool = \Google\Protobuf\Internal\DescriptorPool::getGeneratedPool();
        if (static::$is_initialized == true) {
          return;
        }
        $pool->internalAddGeneratedFile(
            '
�
<google/ads/googleads/v12/enums/served_asset_field_type.protogoogle.ads.googleads.v12.enums"�
ServedAssetFieldTypeEnum"�
ServedAssetFieldType
UNSPECIFIED 
UNKNOWN

HEADLINE_1

HEADLINE_2

HEADLINE_3
DESCRIPTION_1
DESCRIPTION_2B�
"com.google.ads.googleads.v12.enumsBServedAssetFieldTypeProtoPZCgoogle.golang.org/genproto/googleapis/ads/googleads/v12/enums;enums�GAA�Google.Ads.GoogleAds.V12.Enums�Google\\Ads\\GoogleAds\\V12\\Enums�"Google::Ads::GoogleAds::V12::Enumsbproto3
�
1google/ads/googleads/v12/common/asset_usage.protogoogle.ads.googleads.v12.common"�

AssetUsage
asset (	n
served_asset_field_type (2M.google.ads.googleads.v12.enums.ServedAssetFieldTypeEnum.ServedAssetFieldTypeB�
#com.google.ads.googleads.v12.commonBAssetUsageProtoPZEgoogle.golang.org/genproto/googleapis/ads/googleads/v12/common;common�GAA�Google.Ads.GoogleAds.V12.Common�Google\\Ads\\GoogleAds\\V12\\Common�#Google::Ads::GoogleAds::V12::Commonbproto3'
        , true);
        static::$is_initialized = true;
    }
}

