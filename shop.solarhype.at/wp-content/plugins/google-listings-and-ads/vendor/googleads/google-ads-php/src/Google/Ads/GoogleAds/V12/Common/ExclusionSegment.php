<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v12/common/audiences.proto

namespace Google\Ads\GoogleAds\V12\Common;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * An audience segment to be excluded from an audience.
 *
 * Generated from protobuf message <code>google.ads.googleads.v12.common.ExclusionSegment</code>
 */
class ExclusionSegment extends \Google\Protobuf\Internal\Message
{
    protected $segment;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type \Google\Ads\GoogleAds\V12\Common\UserListSegment $user_list
     *           User list segment to be excluded.
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Google\Ads\GoogleAds\V12\Common\Audiences::initOnce();
        parent::__construct($data);
    }

    /**
     * User list segment to be excluded.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v12.common.UserListSegment user_list = 1;</code>
     * @return \Google\Ads\GoogleAds\V12\Common\UserListSegment|null
     */
    public function getUserList()
    {
        return $this->readOneof(1);
    }

    public function hasUserList()
    {
        return $this->hasOneof(1);
    }

    /**
     * User list segment to be excluded.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v12.common.UserListSegment user_list = 1;</code>
     * @param \Google\Ads\GoogleAds\V12\Common\UserListSegment $var
     * @return $this
     */
    public function setUserList($var)
    {
        GPBUtil::checkMessage($var, \Google\Ads\GoogleAds\V12\Common\UserListSegment::class);
        $this->writeOneof(1, $var);

        return $this;
    }

    /**
     * @return string
     */
    public function getSegment()
    {
        return $this->whichOneof("segment");
    }

}

