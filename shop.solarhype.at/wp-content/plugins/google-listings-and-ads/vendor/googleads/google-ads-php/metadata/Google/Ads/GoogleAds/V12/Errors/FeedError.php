<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v12/errors/feed_error.proto

namespace GPBMetadata\Google\Ads\GoogleAds\V12\Errors;

class FeedError
{
    public static $is_initialized = false;

    public static function initOnce() {
        $pool = \Google\Protobuf\Internal\DescriptorPool::getGeneratedPool();
        if (static::$is_initialized == true) {
          return;
        }
        $pool->internalAddGeneratedFile(
            '
�	
0google/ads/googleads/v12/errors/feed_error.protogoogle.ads.googleads.v12.errors"�
FeedErrorEnum"�
	FeedError
UNSPECIFIED 
UNKNOWN
ATTRIBUTE_NAMES_NOT_UNIQUE/
+ATTRIBUTES_DO_NOT_MATCH_EXISTING_ATTRIBUTES.
*CANNOT_SPECIFY_USER_ORIGIN_FOR_SYSTEM_FEED4
0CANNOT_SPECIFY_GOOGLE_ORIGIN_FOR_NON_SYSTEM_FEED2
.CANNOT_SPECIFY_FEED_ATTRIBUTES_FOR_SYSTEM_FEED4
0CANNOT_UPDATE_FEED_ATTRIBUTES_WITH_ORIGIN_GOOGLE
FEED_REMOVED
INVALID_ORIGIN_VALUE	
FEED_ORIGIN_IS_NOT_USER
 
INVALID_AUTH_TOKEN_FOR_EMAIL
INVALID_EMAIL
DUPLICATE_FEED_NAME
INVALID_FEED_NAME
MISSING_OAUTH_INFO.
*NEW_ATTRIBUTE_CANNOT_BE_PART_OF_UNIQUE_KEY
TOO_MANY_ATTRIBUTES
INVALID_BUSINESS_ACCOUNT3
/BUSINESS_ACCOUNT_CANNOT_ACCESS_LOCATION_ACCOUNT
INVALID_AFFILIATE_CHAIN_ID
DUPLICATE_SYSTEM_FEED
GMB_ACCESS_ERROR5
1CANNOT_HAVE_LOCATION_AND_AFFILIATE_LOCATION_FEEDS#
LEGACY_EXTENSION_TYPE_READ_ONLYB�
#com.google.ads.googleads.v12.errorsBFeedErrorProtoPZEgoogle.golang.org/genproto/googleapis/ads/googleads/v12/errors;errors�GAA�Google.Ads.GoogleAds.V12.Errors�Google\\Ads\\GoogleAds\\V12\\Errors�#Google::Ads::GoogleAds::V12::Errorsbproto3'
        , true);
        static::$is_initialized = true;
    }
}

