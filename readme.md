# EE Donations

## Description

EE Donations is a module for [ExpressionEngine](http://www.expressionengine.com) that brings flexible, simple, and secure
donation payments to your website.  These can be one-time or subscription donations, created automatically on your website.
More specifically, it is a
[module](http://expressionengine.com/public_beta/docs/development/modules.html), [extension](http://expressionengine.com/docs/development/extensions.html),
and [control panel](http://expressionengine.com/public_beta/docs/development/modules.html#control_panel_file) for ExpressionEngine.

It integrates easily with a few simple [template tags](/docs/template_tags).

EE Donations uses the free, open source [the OpenGateway billing engine](http://www.github.com/electricfunction/opengateway) to handle all of its billing, automated emails, and
payment gateway integration.  OpenGateway should be downloaded
and installed in a sub-folder or sub-domain of your website prior to setting up your EE Donations plugin.

## Documentation

Documentation is available as part of the release, in the [docs folder](/docs/).

## FAQs

### Is EE Donations compatible with other ExpressionEngine modules?

Yes - there are no known conflicts with other ExpressionEngine modules/plugins.  However, if you do experience an issue,
feel free to contact us and we'll investigate and make sure it's not an issue with the software.

### Is EE Donations only for ExpressionEngine 2.x?

Yes, at this time, EE Donations is only available for ExpressionEngine 2.x.  Our other ExpressionEngine module, [Membrr](http://www.github.com/electricfunction/membrr)
is available for both ExpressionEngine 1.x and 2.x.  However, with the 2.x version being out for almost two years now, we did not develop a 1.x-compatible
version of EE Donations.

### Why build EE Donations using underlying OpenGateway technology?

By allowing OpenGateway to handle all of the complicated, multi-gateway billing parts of the plugin, we free ourselves up
from having to develop all of this code with the ExpressionEngine framework.  We can also do things like port the code to new
platforms (like from EE1.6.x to EE2.0) in a _much_ shorter timeframe.

Finally, by using OpenGateway, we give you an enterprise-class billing engine and API that you can use to extend your
EE Donations-powered website or on new projects.

## History

EE Donations was developed by [Brock Ferguson](http://www.brockferguson.com), founder of Electric Function, Inc. After Electric Function was acquired,
EE Donations was open-sourced by the new owners.