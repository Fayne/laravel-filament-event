<p align="center"><img src="https://res.cloudinary.com/fayne/image/upload/v1787643269/fayne_logo.png" width="300"></p>

# Laravel Filament Event

简体中文 | [English](docs/README.en.md)

活动与预约管理，适用于 **PHP 8.4+、Laravel 13、Filament 5**。

## 功能与边界

基于 CMS Core、Store Locator 和二维码包，提供活动、分类、规则、时段、预约订单及签到，支持活动/门店/时段库存策略。

## 安装

先在宿主 Composer 配置本私有包及所需 `fayne/*` 依赖的软件源，再执行：

```bash
composer require fayne/laravel-filament-event
php artisan vendor:publish --tag=filament-event-config
php artisan migrate
```

Laravel 自动发现服务提供者。仅提供字段的包无需注册 Panel 插件；业务包按下面的示例注册。依赖版本以 [composer.json](composer.json) 为准，不要复制开发宿主的私有仓库凭据。

## 使用与配置

```php
use Fayne\Event\Filament\EventPlugin;

$panel->plugins([EventPlugin::make()]);
```

在 `config/filament-event.php` 配置 `models.*`、`admin_model`、API 前缀、中间件及预约必填字段。先完成 CMS 的 guard/管理员和 Store Locator 配置，再注册插件。

运行 `php artisan cms:install` 汇总模块权限目录；已有角色不会自动获得新增权限，需显式授权。模块自带 `cms:event:init`，`--demo` 用于本地追加示例数据。预约订单权限使用 `tenant.*EventOrder`，独立于 Commerce 的订单权限。

默认 API 前缀 `/modules/events/api/v1`：

| 方法 | 路径 | 用途 |
| --- | --- | --- |
| GET | `/events`、`/events/{event}` | 浏览活动 |
| GET | `/event-categories`、`/event-categories/{eventCategory}` | 浏览分类 |
| POST | `/events/{event}/reservations` | 按活动设置进行访客或认证预约 |
| GET | `/me/reservations`、`/me/reservations/{order}` | 查询本人预约 |
| POST | `/me/reservations/{order}/cancel` | 取消本人预约 |
| POST | `/reservations/check-in` | 签到 |

本人预约和签到使用配置的认证中间件；宿主须配置 Sanctum。另提供默认 `/modules/events/api/v2` 的活动和分类读取接口及内容语言解析。内容语言配置遵循 CMS Core，独立于后台界面语言。部署时验证真实数据库并发库存、身份授权及外部二维码服务。

## 测试与上线

在本包目录执行 `composer install`、`composer test`、`composer format`。自动化检查不能代替宿主表单和真实环境验收。

## 合作与贡献

通过 Issue 描述需求或缺陷，提供复现步骤、版本和预期行为；提交合并请求时补充相关验证，并说明尚未验证的环境。请勿提交密钥或业务敏感数据。商业合作及授权请联系 [Fayne Guo](mailto:fayne.guo@gmail.com)。

## 许可证

采用[专有许可证](LICENSE.md)。未经授权不得复制、分发或使用；第三方依赖仍遵循各自许可证。

## 开发与安全

[开发与 CI 约定](CONTRIBUTING.md) · [安全报告](SECURITY.md)。运行 `composer check` 检查语法和格式；有独立套件时同时运行测试。包不提交 `composer.lock`。
