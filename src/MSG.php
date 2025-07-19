<?php
namespace Opensitez\Simplicity;

enum MSG
{
    case onInit;
    case onComponentLoad;
    case onParseSite;
    case onParseRoute;
    case onAuth;
    case onRequireLogin;
    case onSetLayout;
    case onSetPalette;
    case onSetBlocks;
    case onSetMenus;
    case onRenderPage;
    case onRenderAdminPage;
    case onRenderBlock;
    case onRegisterTemplateEngine;
    case onRegisterDirective;
    case onRouteNotFound;
    case onShutdown;
    case onError;
}
