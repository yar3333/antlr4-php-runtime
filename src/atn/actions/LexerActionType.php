<?php

namespace Antlr4\Atn\Actions;

class LexerActionType
{
    const CHANNEL = 0;//The type of a {@link LexerChannelAction} action.
    const CUSTOM = 1;//The type of a {@link LexerCustomAction} action.
    const MODE = 2;//The type of a {@link LexerModeAction} action.
    const MORE = 3;//The type of a {@link LexerMoreAction} action.
    const POP_MODE = 4;//The type of a {@link LexerPopModeAction} action.
    const PUSH_MODE = 5;//The type of a {@link LexerPushModeAction} action.
    const SKIP = 6;//The type of a {@link LexerSkipAction} action.
    const TYPE = 7;//The type of a {@link LexerTypeAction} action.
}