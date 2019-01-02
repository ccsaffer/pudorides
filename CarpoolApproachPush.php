<?php
namespace api\components\LongPolling\customers;

use common\components\enum\PushesActions;
use common\models\CarpoolPoint;

class CarpoolApproachPush extends CarpoolPointPush
{
    protected $action = PushesActions::CARPOOL_POINT_APPROACH;
}