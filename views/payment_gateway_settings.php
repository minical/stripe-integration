<div class="page-header">
	<h2><?php echo l('stripe-integration/payment gateway settings'); ?></h2>
</div>

    <?php if($this->session->flashdata('setting_update')){ ?>
        <div class="alert alert-success">
            <?php echo $this->session->flashdata('setting_update'); ?>
        </div>
    <?php } //$this->session->unset_flashdata('setting_update'); ?>

<div class="form-horizontal" style="min-height: 165px;">
	<div class="form-group">
		<label for="current_time" class="col-sm-3 control-label">
			<?php echo l('stripe-integration/Current Payment Gateway'); ?>
		</label>
		<div class="col-sm-9">
			<select name="payment_gateway" class="form-control">
				
			</select>	
		</div>
	</div>

	<div id="form-div">
	</div>

	<div class="col-sm-12 text-center">
    <?php 
    if (isset($stripeData['stripe_secret_key']) && $stripeData['stripe_secret_key'] != "" && isset($stripeData['stripe_publishable_key'])  && $stripeData['stripe_publishable_key'] != "" ) { ?>    
     
    <button class="btn btn-light update" id="update-button" ><?php echo l('stripe-integration/Update', true); ?></button>
    <button type="button" class="btn btn-danger deconfigure-stripe" ><?=l("Deconfigure");?></button>
    <?php } else { ?>
        <button class="btn btn-light update" id="update-button" ><?php echo l('stripe-integration/Update', true); ?></button>
    <?php } ?>
    </div>
</div>	
