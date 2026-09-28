<?php

namespace App\Enums;

enum ConversationStage: string
{
    case AwaitingIdentity = 'awaiting_identity';
    case AwaitingIssue = 'awaiting_issue';
    case AwaitingConfirmation = 'awaiting_confirmation';
    case HandedOff = 'handed_off';
}
