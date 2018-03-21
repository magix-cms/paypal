{extends file="layout.tpl"}
{block name='head:title'}paypal{/block}
{block name='body:id'}paypal{/block}
{block name='article:header'}
    <h1 class="h2">paypal</h1>
{/block}
{block name='article:content'}
    {if {employee_access type="view" class_name=$cClass} eq 1}
        <div class="panels row">
            <section class="panel col-ph-12">
                {if $debug}
                    {$debug}
                {/if}
                <header class="panel-header">
                    <h2 class="panel-heading h5">{#paypal_management#}</h2>
                </header>
                <div class="panel-body panel-body-form">
                    <div class="mc-message-container clearfix">
                        <div class="mc-message"></div>
                    </div>
                    <div class="row">
                        <form id="paypal_config" action="{$smarty.server.SCRIPT_NAME}?controller={$smarty.get.controller}&amp;action=edit" method="post" class="validate_form edit_form col-xs-12 col-md-6">
                            <div class="row">
                                <div class="col-xs-12 col-sm-10">
                                    <div class="form-group">
                                        <label for="clientId">clientId :</label>

                                        <div class="input-group">
                                            <div class="input-group-addon"><span class="fa fa-key"></span></div>
                                            <input type="text" class="form-control" id="clientId" name="clientId" value="{$paypal.clientid}" size="50" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-xs-12 col-sm-10">
                                    <div class="form-group">
                                        <label for="clientSecret">clientSecret :</label>
                                        <div class="input-group">
                                            <div class="input-group-addon"><span class="fa fa-lock"></span></div>
                                            <input type="text" class="form-control" id="clientSecret" name="clientSecret" value="{$paypal.clientsecret}" size="50" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-xs-12 col-sm-6">
                                    <div class="form-group">
                                        <label for="mode">Mode* :</label>

                                        <select name="mode" id="mode" class="form-control required" required>
                                            <option value="" selected="">{#select_mode#}</option>
                                            <option selected="" value="sandbox"{if $paypal.mode eq "sandbox"} selected{/if}>Sandbox</option>
                                            <option value="live"{if $paypal.mode eq "live"} selected{/if}>Live</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-xs-12 col-sm-4">
                                    <div class="form-group">
                                        <label for="log">{#log#|ucfirst} :</label>
                                        <input id="log" data-toggle="toggle" type="checkbox" name="log" data-toggle="toggle" type="checkbox" data-on="oui" data-off="non" data-onstyle="primary" data-offstyle="default"{if $paypal.log} checked{/if}>
                                    </div>
                                </div>
                            </div>
                            <div id="submit">
                                <button class="btn btn-main-theme" type="submit" name="action" value="edit">{#save#|ucfirst}</button>
                            </div>
                        </form>
                        <div class="col-xs-12 col-md-6">
                            <a href="https://www.paypal.com/fr/merchantsignup/applicationChecklist?signupType=CREATE_NEW_ACCOUNT&productIntentId=wp_standard" class="btn btn-main-theme targetblank">
                                <span class="fa fa-paypal"></span> {#create_a_paypal_business_account#|ucfirst}
                            </a>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    {else}
        {include file="section/brick/viewperms.tpl"}
    {/if}
{/block}