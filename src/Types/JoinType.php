<?php

namespace Abdulelahragih\QueryBuilder\Types;

enum JoinType: string
{
    case Inner = 'INNER';
    case Left = 'LEFT';
    case Right = 'RIGHT';
    case Full = 'FULL';
}