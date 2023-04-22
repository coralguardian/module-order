<?php

namespace D4rk0snet\CoralOrder\Enums;

enum RecipientReceptionMode : string
{
    case MYSELF = 'myself';
    case IMMEDIATE = 'immediate';
    case LATER = 'later';

}