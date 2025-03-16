# beGateway Payment Gateway for WHMCS

## Features

* Refunds through payment gateway
* Automatically updates invoice payment status

## Installation

### Prerequisites

* Working WHMCS installation (v6.x or above)
* PHP 5.3 or above

### Installation steps

1. Download the [latest release](https://github.com/begateway/whmcs-payment-module/releases)
2. Unpack it
3. Copy/upload the following folder `modules` to `<whmcs dir>`.
4. Go to the WHMCS admin area and go to `System settings -> Payment Gateways`.
5. Click `beGateway` to activate the payment gateway. If there is no `beGateway` there, then visit `Apps & Integrations` to active the `beGateway` payment gateway.

## Test data

If you setup the module with values as follows:

  * Shop Id __361__
  * Shop Key __b8647b68898b084b836474ed8d61ffe117c9a01168d867f24953b776ddcb134d__
  * Gateway Domain __demo-gateway.begateway.com__
  * Checkout Domain __checkout.begateway.com__

Then you can use the test data to make a test payment:

* card number __4200000000000000__
* card name __JOHN DOE__
* card expiry __01/30__ to get a success payment
* card expiry __10/30__ to get a failed payment
* CVC __123__

# Модуль оплаты beGateway для WHMCS

## Возможности

* Возвраты через платёжный шлюз
* Автоматическое обновления статуса оплаты инвойса

## Установка

### Требования

* Рабочая инсталяция WHMCS (v6.x и выше)
* PHP 5.3 и вышле

### Шаги по установке

1. Скачайте [последнюю версию](https://github.com/begateway/whmcs-payment-module/releases)
2. Распакуйте архив
3. Скопируйте/загрузите директорию `modules` в директорию `<whmcs dir>`.
4. Зайдите в зону администратора WHMCS и прейдите `Настройки -> Платежные шлюзы`. 
5. Нажмите `beGateway`, чтобы активировать платежный шлюз. Если там нет `beGateway`, то активируйте интеграцию через меню `Приложения и интеграции`

## Тестовые данные

Если вы настроете модуль со следующими значениями

  * Id магазина __361__
  * Секретный ключ  __b8647b68898b084b836474ed8d61ffe117c9a01168d867f24953b776ddcb134d__
  * Домен страницы оплаты __checkout.begateway.com__
  * Домен платёжного шлюза __demo-gateway.begateway.com__

то вы сможете уже
осуществить тестовый платеж в вашем магазине. Используйте следующие
данные тестовой карты:

  * номер карты __4200000000000000__
  * имя на карте __John Doe__
  * срок действия карты __01/30__, чтобы получить успешный платеж
  * срок действия карты __10/30__, чтобы получить неуспешный платеж
  * CVC __123__
