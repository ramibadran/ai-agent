<?php

use App\Mcp\Servers\AtlasCommerceServer;
use Laravel\Mcp\Facades\Mcp;

Mcp::web('/mcp/atlas-commerce', AtlasCommerceServer::class)
    ->middleware(['throttle:60,1']);
