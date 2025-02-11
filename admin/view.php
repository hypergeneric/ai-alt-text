<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

// pull the options
$api_key            = craat()->options()->get( 'api_key' );
$keyword_seeding    = craat()->options()->get( 'keyword_seeding' );
$language           = craat()->options()->get( 'language' );
$generate_on_save   = craat()->options()->get( 'generate_on_save' );
$cron_enabled       = craat()->options()->get( 'cron_enabled' );
$enable_logging     = craat()->options()->get( 'enable_logging' );
$cron_timeout       = craat()->options()->get( 'cron_timeout' );
$save_timeout       = craat()->options()->get( 'save_timeout' );
$stats              = craat()->options()->get( 'stats' );
$stats_total        = 0;
$upload_info        = wp_get_upload_dir();
$logfile            = $upload_info['baseurl'] . "/ai-alt-text/log.txt";

$languages = [
	'en_US' => 'English (US)',
	'es_ES' => 'Spanish (Spain)',
	'ja'    => 'Japanese',
	'de_DE' => 'German',
	'fr_FR' => 'French',
	'en_GB' => 'English (UK)',
	'it_IT' => 'Italian',
	'pt_BR' => 'Portuguese (Brazil)',
	'ru_RU' => 'Russian',
	'pl_PL' => 'Polish',
];

foreach ( $stats as $key => $value ) {
	$stats_total += $value;
}

?>
<div id="admin-view">

	<div id="logo"><img src="<?php echo esc_url( CRAAT_PLUGIN_DIR . 'admin/img/logo.png' ); ?>"></div>
	
	<form id="admin-view-form" autocomplete="off">
		
		<section id="craatWrapper">

			<ul class="tabs">
				<li data-tab="stats"><?php esc_html_e( 'Stats', 'ai-alt-text' ); ?></li>
				<li data-tab="settings"><?php esc_html_e( 'Settings', 'ai-alt-text' ); ?></li>
				<?php if ( $cron_enabled == true ) : ?><li data-tab="cron"><?php esc_html_e( 'CRON', 'ai-alt-text' ); ?></li><?php endif; ?>
				<?php if ( $enable_logging == true ) : ?><li data-tab="log"><?php esc_html_e( 'Log', 'ai-alt-text' ); ?></li><?php endif; ?>
			</ul>

			<ul class="tab__content">

				<li id="tab-stats">
					<div class="content__wrapper">
					
						<h1><?php esc_html_e( 'Total Alt Tags Created', 'ai-alt-text' ); ?>: <?php echo esc_html( $stats_total ); ?></h1>
						<select id="chart-timespan">
							<option value="last-30d" selected><?php esc_html_e( 'Last 30 Days', 'ai-alt-text' ); ?></option>
							<option value="last-60d"><?php esc_html_e( 'Last 60 Days', 'ai-alt-text' ); ?></option>
							<option value="last-1y"><?php esc_html_e( 'Year to date', 'ai-alt-text' ); ?></option>
						</select>
						<div id="chart" class="ajax-group">
							<div class="screen" style="background-image: url( <?php echo esc_url( get_admin_url() . 'images/loading.gif' ); ?> );"></div>
							<div id="chart_div"></div>
						</div>

					</div>
				</li>

				<li id="tab-settings">

					<div class="content__wrapper">

						<div class="field">
							<label for="api_key"><?php esc_html_e( 'API Key', 'ai-alt-text' ); ?></label><br>
							<input id="api_key" name="api_key" type="text" placeholder="" value="<?php echo esc_attr( $api_key ); ?>">
							<div class="desc">
								<?php esc_html_e( 'Your account API Key.', 'ai-alt-text' ); ?>
							</div>
						</div>

						<div class="field">
							<label for="keyword_seeding"><?php esc_html_e( 'Keyword Seed', 'ai-alt-text' ); ?></label><br>
							<input id="keyword_seeding" name="keyword_seeding" type="text" placeholder="<?php esc_attr_e( 'Comma Separated', 'ai-alt-text' ); ?>" value="<?php echo esc_attr( $keyword_seeding ); ?>">
							<div class="desc">
								<?php esc_html_e( 'A comma-separated list of your site\'s keywords you try to rank for.', 'ai-alt-text' ); ?>
							</div>
						</div>

						<div class="field">
							<label for="language"><?php esc_html_e( 'Language', 'ai-alt-text' ); ?></label><br>
							<select id="language" name="language">
								<?php foreach ( $languages as $code => $name ) {
									printf(
										'<option value="%s" %s>%s</option>',
										esc_attr( $code ),
										selected( $language, $code, false ),
										esc_html( $name )
									);
								} ?>
							</select>
							<div class="desc">
								<?php esc_html_e( 'Select the language for generating alt text.', 'ai-alt-text' ); ?>
							</div>
						</div>

						<div class="checkbox">
							<div class="check">
								<input type="checkbox" 
									name="generate_on_save" id="generate_on_save" 
									value="<?php echo esc_attr( $generate_on_save ? 'true' : 'false' ); ?>" 
									<?php if ( $generate_on_save == true ) : ?>checked="checked"<?php endif; ?>
								/>
							</div>
							<div class="label">
								<label for="generate_on_save"><?php esc_html_e( 'Generate Alt Tags on Save or Upload', 'ai-alt-text' ); ?></label>
							</div>
							<div class="desc">
								<?php esc_html_e( 'Generate ALT text during the upload process -- disabled by default, as it will add extra time for each image upload.  Bulk processing via CRON is recommended instead.', 'ai-alt-text' ); ?>
							</div>
						</div>

						<div class="checkbox">
							<div class="check">
								<input type="checkbox" 
									name="cron_enabled" id="cron_enabled" 
									value="<?php echo esc_attr( $cron_enabled ? 'true' : 'false' ); ?>" 
									<?php if ( $cron_enabled == true ) : ?>checked="checked"<?php endif; ?>
								/>
							</div>
							<div class="label">
								<label for="cron_enabled"><?php esc_html_e( 'Enable Bulk Alt Tag Creation', 'ai-alt-text' ); ?></label>
							</div>
							<div class="desc">
								<?php esc_html_e( 'When checked, the plugin will automatically search for and update images in the Media Library without Alt text and populate them.', 'ai-alt-text' ); ?>
							</div>
						</div>

						<div class="checkbox">
							<div class="check">
								<input type="checkbox" 
									name="enable_logging" id="enable_logging" 
									value="<?php echo esc_attr( $enable_logging ? 'true' : 'false' ); ?>" 
									<?php if ( $enable_logging == true ) : ?>checked="checked"<?php endif; ?>
								/>
							</div>
							<div class="label">
								<label for="enable_logging"><?php esc_html_e( 'Enable Logging', 'ai-alt-text' ); ?></label>
							</div>
							<div class="desc">
								<?php esc_html_e( 'Log all actions taken to a local file on the server, in the uploads folder.  Be advised: this log can grow very large, and is not hidden on the file system.  Proceed with caution.', 'ai-alt-text' ); ?>
							</div>
						</div>
						
						<input id="submitForm" class="button button-primary" name="submitForm" type="submit" value="<?php esc_attr_e( 'Save', 'ai-alt-text' ); ?>" />
						
					</div>

				</li>

				<li id="tab-cron">

					<div class="content__wrapper">

						<div class="field">
							<label for="cron_timeout"><?php esc_html_e( 'Generate Timeout', 'ai-alt-text' ); ?></label><br>
							<input id="cron_timeout" name="cron_timeout" type="number" placeholder="<?php esc_attr_e( 'In Seconds', 'ai-alt-text' ); ?>" value="<?php echo esc_attr( $cron_timeout ); ?>">
							<div class="desc">
								<?php esc_html_e( 'Define the maximum duration in seconds that the plugin should spend on a generate task during each scheduled CRON job before timing out.', 'ai-alt-text' ); ?>
							</div>
						</div>
						
						<input id="submitForm" class="button button-primary" name="submitForm" type="submit" value="<?php esc_attr_e( 'Save', 'ai-alt-text' ); ?>" />
						
					</div>

				</li>

				<li id="tab-log">
					<div class="content__wrapper">

						<div id="logs" class="ajax-group">
							
							<div class="screen" style="background-image: url( <?php echo esc_url( get_admin_url() . 'images/loading.gif' ); ?> );"></div>

							<table>
								<thead>
									<th class="time" colspan="1"><span class='handle'><?php esc_html_e( 'Time', 'ai-alt-text' ); ?></span></th>
									<th colspan="1"><span class='handle'><?php esc_html_e( 'Log', 'ai-alt-text' ); ?></span></th>
								</thead>
								<tbody>
									<tr class="seed">
										<td class="time"><span class='timestamp'></span></td>
										<td><span class='logdata'></span></td>
									</tr>
								</tbody>
							</table>

							<button class="button logs-clear" data-confirm="<?php esc_attr_e( 'Are you sure?  This will delete all log data permanently.', 'ai-alt-text' ); ?>"><?php esc_html_e( 'Clear', 'ai-alt-text' ); ?></button>
							<a class="button logs-download" href="<?php echo esc_attr( $logfile ); ?>" target="_blank" title="<?php esc_attr_e( 'Download', 'ai-alt-text' ); ?>">&#10515;</a>
							<button class="button logs-refresh" title="<?php esc_attr_e( 'Refresh', 'ai-alt-text' ); ?>">&#10226;</button>
							<button disabled class="button button-primary logs-start" title="<?php esc_attr_e( 'Rewind', 'ai-alt-text' ); ?>">&#171;</button>
							<button disabled class="button button-primary logs-prev" title="<?php esc_attr_e( 'Previous', 'ai-alt-text' ); ?>">&#8249;</button>
							<button disabled class="button button-primary logs-next" title="<?php esc_attr_e( 'Next', 'ai-alt-text' ); ?>">&#8250;</button>
							<button disabled class="button button-primary logs-end" title="<?php esc_attr_e( 'Forward', 'ai-alt-text' ); ?>">&#187;</button>
							<span class="meta"><?php esc_html_e( 'Page', 'ai-alt-text' ); ?> <span class="page-index"></span> <?php esc_html_e( 'of', 'ai-alt-text' ); ?> <span class="page-count"></span></span>
						
						</div>
						
					</div>
				</li>

			</ul>
		</section>

	</form>
</div>
