<?php
/**
 * Legal document generator for WPSimpleCompliance.
 *
 * The generator provides structured defaults and document templates. The site
 * owner remains responsible for verifying the actual processing activities.
 */

if (! defined('ABSPATH')) {
	exit;
}

final class SPCP_Legal_Generator {
	const AUTHORITY_URL = 'https://www.garanteprivacy.it/';

	public static function defaults() {
		$admin_email = (string) get_option('admin_email');
		$site_name = wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES);

		return array(
			'policy_languages' => 'it,en',
			'privacy_policy_mode' => 'generated',
			'external_privacy_url' => '',
			'controller_type' => 'organization',
			'controller_name' => $site_name,
			'controller_legal_name' => $site_name,
			'controller_tax_id' => '',
			'controller_vat' => '',
			'controller_address' => '',
			'controller_email' => is_email($admin_email) ? $admin_email : '',
			'controller_pec' => '',
			'controller_phone' => '',
			'privacy_contact_email' => is_email($admin_email) ? $admin_email : '',
			'dpo_name' => '',
			'dpo_email' => '',
			'eu_representative' => '',
			'processors_summary' => 'Fornitori tecnici, hosting provider, manutentori IT, piattaforme di sicurezza, servizi di posta elettronica e altri responsabili nominati ove necessario.',
			'data_categories' => 'Dati di navigazione, identificativi tecnici online, dati inviati tramite moduli, dati di contatto, preferenze cookie e dati necessari alla sicurezza del sito.',
			'data_subjects' => 'Visitatori del sito, utenti che compilano moduli, clienti, fornitori, candidati, genitori o tutori se pertinenti al servizio offerto.',
			'data_sources' => 'Dati forniti direttamente dall\'interessato e dati tecnici generati durante la navigazione.',
			'processing_locations' => 'Unione Europea e, solo se necessario per servizi tecnici, paesi terzi con garanzie adeguate.',
			'third_country_transfers' => 'none',
			'transfer_safeguards' => 'Decisioni di adeguatezza, clausole contrattuali standard o ulteriori garanzie previste dal GDPR, ove applicabili.',
			'retention_general' => 'Per il tempo necessario alle finalita indicate, salvo obblighi di legge o necessita di tutela dei diritti.',
			'mandatory_data_note' => 'Il conferimento dei dati richiesti nei moduli e facoltativo, ma il mancato conferimento puo impedire la gestione della richiesta.',
			'automated_decision_making' => '0',
			'automated_decision_text' => 'Non sono previsti processi decisionali automatizzati che producano effetti giuridici o analoghi effetti significativi sull\'interessato.',
			'minors_data' => '0',
			'minors_data_text' => 'Il sito non richiede intenzionalmente dati di minori salvo quanto necessario ai servizi richiesti e nel rispetto delle norme applicabili.',
			'complaint_authority_name' => 'Garante per la protezione dei dati personali',
			'complaint_authority_url' => self::AUTHORITY_URL,
			'enable_contact_forms' => '1',
			'legal_basis_contact' => 'Esecuzione di misure precontrattuali o contrattuali richieste dall\'interessato; legittimo interesse a rispondere alle richieste.',
			'retention_contact' => 'Fino a 24 mesi dalla gestione della richiesta, salvo ulteriore necessita documentale o obbligo di legge.',
			'enable_newsletter' => '0',
			'legal_basis_newsletter' => 'Consenso dell\'interessato.',
			'retention_newsletter' => 'Fino a revoca del consenso o disiscrizione.',
			'enable_marketing' => '0',
			'legal_basis_marketing' => 'Consenso dell\'interessato.',
			'retention_marketing' => 'Fino a revoca del consenso e comunque secondo i limiti dichiarati dal titolare.',
			'enable_analytics' => '1',
			'legal_basis_analytics' => 'Consenso, salvo analytics configurati come tecnici con dati aggregati e minimizzati secondo le indicazioni dell\'Autorita.',
			'retention_analytics' => 'Secondo configurazione del servizio; per GA4 di norma fino a 14 mesi salvo diversa configurazione.',
			'enable_security' => '1',
			'legal_basis_security' => 'Legittimo interesse del titolare e necessita tecnica di garantire sicurezza, integrita e prevenzione abusi.',
			'retention_security' => 'Log tecnici per il periodo strettamente necessario alla sicurezza e manutenzione.',
			'enable_ecommerce' => '0',
			'legal_basis_ecommerce' => 'Esecuzione del contratto e obblighi legali, fiscali e contabili.',
			'retention_ecommerce' => 'Per i tempi previsti dalla normativa civile, fiscale e contabile applicabile.',
			'enable_legal_obligations' => '1',
			'legal_basis_legal_obligations' => 'Obbligo legale e tutela dei diritti del titolare.',
			'retention_legal_obligations' => 'Per i termini di prescrizione e conservazione previsti dalla legge.',
			'custom_processing_notes' => '',
		);
	}

	public static function sanitize($settings, $defaults) {
		$text_fields = array(
			'policy_languages',
			'privacy_policy_mode',
			'controller_type',
			'controller_name',
			'controller_legal_name',
			'controller_tax_id',
			'controller_vat',
			'controller_address',
			'controller_email',
			'controller_pec',
			'controller_phone',
			'privacy_contact_email',
			'dpo_name',
			'dpo_email',
			'eu_representative',
			'processing_locations',
			'third_country_transfers',
			'complaint_authority_name',
			'complaint_authority_url',
		);

		$textarea_fields = array(
			'processors_summary',
			'data_categories',
			'data_subjects',
			'data_sources',
			'transfer_safeguards',
			'retention_general',
			'mandatory_data_note',
			'automated_decision_text',
			'minors_data_text',
			'legal_basis_contact',
			'retention_contact',
			'legal_basis_newsletter',
			'retention_newsletter',
			'legal_basis_marketing',
			'retention_marketing',
			'legal_basis_analytics',
			'retention_analytics',
			'legal_basis_security',
			'retention_security',
			'legal_basis_ecommerce',
			'retention_ecommerce',
			'legal_basis_legal_obligations',
			'retention_legal_obligations',
			'custom_processing_notes',
		);

		$checkbox_fields = array(
			'enable_contact_forms',
			'enable_newsletter',
			'enable_marketing',
			'enable_analytics',
			'enable_security',
			'enable_ecommerce',
			'enable_legal_obligations',
			'automated_decision_making',
			'minors_data',
		);

		$sanitized = array();
		foreach ($text_fields as $field) {
			if ('external_privacy_url' === $field || 'complaint_authority_url' === $field) {
				$sanitized[$field] = esc_url_raw($settings[$field] ?? $defaults[$field]);
				continue;
			}
			if (in_array($field, array('controller_email', 'privacy_contact_email', 'dpo_email'), true)) {
				$sanitized[$field] = sanitize_email($settings[$field] ?? $defaults[$field]);
				continue;
			}
			$sanitized[$field] = sanitize_text_field(wp_specialchars_decode((string) ($settings[$field] ?? $defaults[$field]), ENT_QUOTES));
		}

		$sanitized['external_privacy_url'] = esc_url_raw($settings['external_privacy_url'] ?? $defaults['external_privacy_url']);

		foreach ($textarea_fields as $field) {
			$sanitized[$field] = sanitize_textarea_field(wp_specialchars_decode((string) ($settings[$field] ?? $defaults[$field]), ENT_QUOTES));
		}

		foreach ($checkbox_fields as $field) {
			$sanitized[$field] = empty($settings[$field]) ? '0' : '1';
		}

		if (! in_array($sanitized['privacy_policy_mode'], array('generated', 'external'), true)) {
			$sanitized['privacy_policy_mode'] = 'generated';
		}
		if (! in_array($sanitized['third_country_transfers'], array('none', 'possible', 'yes'), true)) {
			$sanitized['third_country_transfers'] = 'none';
		}

		return $sanitized;
	}

	public static function render_admin_fields($settings, $option_name) {
		?>
		<h2>Dati legali per policy automatiche</h2>
		<p>Compila questi campi una volta per sito. Il plugin genera privacy policy e cookie policy multilingua con una base coerente con GDPR e linee guida cookie italiane.</p>
		<table class="form-table" role="presentation">
			<?php self::input($option_name, $settings, 'policy_languages', 'Lingue documenti', 'Esempio: it,en. La cookie policy e la privacy policy vengono generate per tutte le lingue indicate.'); ?>
			<?php self::select($option_name, $settings, 'privacy_policy_mode', 'Privacy policy', array('generated' => 'Generata dal plugin', 'external' => 'Pagina o URL esterno'), 'Se usi una policy gia approvata puoi indicarne l URL sotto.'); ?>
			<?php self::input($option_name, $settings, 'external_privacy_url', 'URL privacy policy esterna', 'Opzionale. Se valorizzato e la modalita e esterna, il banner punta a questo URL.'); ?>
			<?php self::input($option_name, $settings, 'controller_legal_name', 'Denominazione titolare'); ?>
			<?php self::input($option_name, $settings, 'controller_tax_id', 'Codice fiscale'); ?>
			<?php self::input($option_name, $settings, 'controller_vat', 'Partita IVA'); ?>
			<?php self::textarea($option_name, $settings, 'controller_address', 'Sede / indirizzo titolare'); ?>
			<?php self::input($option_name, $settings, 'controller_email', 'Email titolare'); ?>
			<?php self::input($option_name, $settings, 'controller_pec', 'PEC titolare'); ?>
			<?php self::input($option_name, $settings, 'controller_phone', 'Telefono titolare'); ?>
			<?php self::input($option_name, $settings, 'privacy_contact_email', 'Email esercizio diritti'); ?>
			<?php self::input($option_name, $settings, 'dpo_name', 'DPO/RPD nome'); ?>
			<?php self::input($option_name, $settings, 'dpo_email', 'DPO/RPD email'); ?>
			<?php self::textarea($option_name, $settings, 'eu_representative', 'Rappresentante UE', 'Solo se necessario.'); ?>
			<?php self::textarea($option_name, $settings, 'data_subjects', 'Categorie interessati'); ?>
			<?php self::textarea($option_name, $settings, 'data_categories', 'Categorie dati trattati'); ?>
			<?php self::textarea($option_name, $settings, 'data_sources', 'Fonti dei dati'); ?>
			<?php self::textarea($option_name, $settings, 'processors_summary', 'Destinatari / responsabili'); ?>
			<?php self::textarea($option_name, $settings, 'processing_locations', 'Luoghi di trattamento'); ?>
			<?php self::select($option_name, $settings, 'third_country_transfers', 'Trasferimenti extra SEE', array('none' => 'No / non previsti', 'possible' => 'Possibili tramite fornitori tecnici', 'yes' => 'Si'), 'Se usi Google, Meta o CDN extra UE, verifica garanzie e configurazioni.'); ?>
			<?php self::textarea($option_name, $settings, 'transfer_safeguards', 'Garanzie trasferimenti'); ?>
			<?php self::textarea($option_name, $settings, 'retention_general', 'Conservazione generale'); ?>
			<?php self::textarea($option_name, $settings, 'mandatory_data_note', 'Obbligatorieta conferimento dati'); ?>
			<?php self::input($option_name, $settings, 'complaint_authority_name', 'Autorita reclamo'); ?>
			<?php self::input($option_name, $settings, 'complaint_authority_url', 'URL autorita reclamo'); ?>
		</table>

		<h2>Trattamenti dichiarati</h2>
		<table class="form-table" role="presentation">
			<?php self::checkbox_group($option_name, $settings, array(
				'enable_contact_forms' => 'Richieste da form/email',
				'enable_newsletter' => 'Newsletter',
				'enable_marketing' => 'Marketing/profilazione',
				'enable_analytics' => 'Analytics/statistiche',
				'enable_security' => 'Sicurezza, antispam, log tecnici',
				'enable_ecommerce' => 'E-commerce/donazioni/pagamenti',
				'enable_legal_obligations' => 'Obblighi legali e tutela diritti',
				'automated_decision_making' => 'Decisioni automatizzate/profilazione con effetti significativi',
				'minors_data' => 'Trattamento dati di minori',
			)); ?>
			<?php self::textarea($option_name, $settings, 'legal_basis_contact', 'Base giuridica contatti'); ?>
			<?php self::textarea($option_name, $settings, 'retention_contact', 'Conservazione contatti'); ?>
			<?php self::textarea($option_name, $settings, 'legal_basis_newsletter', 'Base giuridica newsletter'); ?>
			<?php self::textarea($option_name, $settings, 'retention_newsletter', 'Conservazione newsletter'); ?>
			<?php self::textarea($option_name, $settings, 'legal_basis_marketing', 'Base giuridica marketing'); ?>
			<?php self::textarea($option_name, $settings, 'retention_marketing', 'Conservazione marketing'); ?>
			<?php self::textarea($option_name, $settings, 'legal_basis_analytics', 'Base giuridica analytics'); ?>
			<?php self::textarea($option_name, $settings, 'retention_analytics', 'Conservazione analytics'); ?>
			<?php self::textarea($option_name, $settings, 'legal_basis_security', 'Base giuridica sicurezza'); ?>
			<?php self::textarea($option_name, $settings, 'retention_security', 'Conservazione sicurezza'); ?>
			<?php self::textarea($option_name, $settings, 'legal_basis_ecommerce', 'Base giuridica e-commerce/donazioni'); ?>
			<?php self::textarea($option_name, $settings, 'retention_ecommerce', 'Conservazione e-commerce/donazioni'); ?>
			<?php self::textarea($option_name, $settings, 'legal_basis_legal_obligations', 'Base giuridica obblighi legali'); ?>
			<?php self::textarea($option_name, $settings, 'retention_legal_obligations', 'Conservazione obblighi legali'); ?>
			<?php self::textarea($option_name, $settings, 'automated_decision_text', 'Nota decisioni automatizzate'); ?>
			<?php self::textarea($option_name, $settings, 'minors_data_text', 'Nota dati minori'); ?>
			<?php self::textarea($option_name, $settings, 'custom_processing_notes', 'Note aggiuntive'); ?>
		</table>
		<?php
	}

	private static function input($option_name, $settings, $key, $label, $help = '') {
		?>
		<tr>
			<th scope="row"><label for="spcp-<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></label></th>
			<td>
				<input id="spcp-<?php echo esc_attr($key); ?>" class="regular-text code" name="<?php echo esc_attr($option_name); ?>[<?php echo esc_attr($key); ?>]" value="<?php echo esc_attr($settings[$key] ?? ''); ?>">
				<?php if ($help) : ?><p class="description"><?php echo esc_html($help); ?></p><?php endif; ?>
			</td>
		</tr>
		<?php
	}

	private static function textarea($option_name, $settings, $key, $label, $help = '') {
		?>
		<tr>
			<th scope="row"><label for="spcp-<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></label></th>
			<td>
				<textarea id="spcp-<?php echo esc_attr($key); ?>" class="large-text code" rows="3" name="<?php echo esc_attr($option_name); ?>[<?php echo esc_attr($key); ?>]"><?php echo esc_textarea($settings[$key] ?? ''); ?></textarea>
				<?php if ($help) : ?><p class="description"><?php echo esc_html($help); ?></p><?php endif; ?>
			</td>
		</tr>
		<?php
	}

	private static function select($option_name, $settings, $key, $label, $choices, $help = '') {
		?>
		<tr>
			<th scope="row"><label for="spcp-<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></label></th>
			<td>
				<select id="spcp-<?php echo esc_attr($key); ?>" name="<?php echo esc_attr($option_name); ?>[<?php echo esc_attr($key); ?>]">
					<?php foreach ($choices as $value => $text) : ?>
						<option value="<?php echo esc_attr($value); ?>" <?php selected($settings[$key] ?? '', $value); ?>><?php echo esc_html($text); ?></option>
					<?php endforeach; ?>
				</select>
				<?php if ($help) : ?><p class="description"><?php echo esc_html($help); ?></p><?php endif; ?>
			</td>
		</tr>
		<?php
	}

	private static function checkbox_group($option_name, $settings, $fields) {
		?>
		<tr>
			<th scope="row">Finalita attive</th>
			<td>
				<?php foreach ($fields as $key => $label) : ?>
					<label style="display:block;margin:0 0 6px;">
						<input type="checkbox" name="<?php echo esc_attr($option_name); ?>[<?php echo esc_attr($key); ?>]" value="1" <?php checked($settings[$key] ?? '0', '1'); ?>>
						<?php echo esc_html($label); ?>
					</label>
				<?php endforeach; ?>
			</td>
		</tr>
		<?php
	}

	public static function languages($settings) {
		$raw = sanitize_text_field($settings['policy_languages'] ?? 'it,en');
		$langs = array_filter(array_map('trim', explode(',', strtolower($raw))));
		$langs = array_values(array_intersect($langs, array('it', 'en')));
		return $langs ? $langs : array('it', 'en');
	}

	public static function privacy_public_url($settings) {
		if ('external' === ($settings['privacy_policy_mode'] ?? 'generated') && ! empty($settings['external_privacy_url'])) {
			return esc_url_raw($settings['external_privacy_url']);
		}

		return esc_url_raw($settings['privacy_url'] ?? home_url('/privacy-policy/'));
	}

	public static function privacy_policy_markup($settings, $settings_link = '', $include_title = true) {
		if ('external' === ($settings['privacy_policy_mode'] ?? 'generated') && ! empty($settings['external_privacy_url'])) {
			return self::external_privacy_markup($settings, $include_title);
		}

		ob_start();
		?>
		<section class="simple-policy simple-policy--privacy">
			<?php if ($include_title) : ?><h2>Privacy policy</h2><?php endif; ?>
			<?php self::language_nav($settings); ?>
			<?php foreach (self::languages($settings) as $lang) : ?>
				<article class="simple-policy__language" lang="<?php echo esc_attr($lang); ?>">
					<?php echo 'it' === $lang ? self::privacy_it($settings, $settings_link) : self::privacy_en($settings, $settings_link); ?>
				</article>
			<?php endforeach; ?>
		</section>
		<?php
		return ob_get_clean();
	}

	private static function external_privacy_markup($settings, $include_title = true) {
		$url = esc_url($settings['external_privacy_url']);
		ob_start();
		?>
		<section class="simple-policy simple-policy--privacy">
			<?php if ($include_title) : ?><h2>Privacy policy</h2><?php endif; ?>
			<p>La privacy policy ufficiale e disponibile qui: <a href="<?php echo $url; ?>"><?php echo $url; ?></a>.</p>
			<p>The official privacy policy is available here: <a href="<?php echo $url; ?>"><?php echo $url; ?></a>.</p>
		</section>
		<?php
		return ob_get_clean();
	}

	private static function privacy_it($settings, $settings_link) {
		$rows = self::processing_rows($settings, 'it');
		ob_start();
		?>
		<h3>Informativa sul trattamento dei dati personali</h3>
		<p>Questa informativa descrive come vengono trattati i dati personali raccolti tramite questo sito ai sensi del Regolamento UE 2016/679.</p>
		<?php echo self::controller_block($settings, 'it'); ?>
		<p><strong>Interessati:</strong> <?php echo esc_html($settings['data_subjects']); ?></p>
		<p><strong>Categorie di dati:</strong> <?php echo esc_html($settings['data_categories']); ?></p>
		<p><strong>Fonte dei dati:</strong> <?php echo esc_html($settings['data_sources']); ?></p>
		<?php echo self::table('Finalita, basi giuridiche e conservazione', array('Finalita', 'Categorie dati', 'Base giuridica', 'Conservazione'), $rows); ?>
		<p><strong>Destinatari e responsabili:</strong> <?php echo esc_html($settings['processors_summary']); ?></p>
		<p><strong>Luoghi di trattamento:</strong> <?php echo esc_html($settings['processing_locations']); ?></p>
		<p><strong>Trasferimenti extra SEE:</strong> <?php echo esc_html(self::transfer_text($settings, 'it')); ?></p>
		<p><strong>Conservazione generale:</strong> <?php echo esc_html($settings['retention_general']); ?></p>
		<p><strong>Conferimento dei dati:</strong> <?php echo esc_html($settings['mandatory_data_note']); ?></p>
		<p><strong>Diritti dell'interessato:</strong> accesso, rettifica, cancellazione, limitazione, opposizione, portabilita quando applicabile, revoca del consenso senza pregiudicare la liceita del trattamento precedente.</p>
		<p><strong>Reclamo:</strong> l'interessato puo proporre reclamo a <?php echo esc_html($settings['complaint_authority_name']); ?><?php echo ! empty($settings['complaint_authority_url']) ? ' - '. esc_html($settings['complaint_authority_url']) : ''; ?>.</p>
		<p><strong>Decisioni automatizzate:</strong> <?php echo esc_html($settings['automated_decision_text']); ?></p>
		<p><strong>Minori:</strong> <?php echo esc_html($settings['minors_data_text']); ?></p>
		<?php if (! empty($settings['custom_processing_notes'])) : ?>
			<p><strong>Note aggiuntive:</strong> <?php echo esc_html($settings['custom_processing_notes']); ?></p>
		<?php endif; ?>
		<?php if ($settings_link) : ?><p>Gestione cookie: <?php echo $settings_link; ?></p><?php endif; ?>
		<p class="lde-policy__updated">Ultimo aggiornamento: <?php echo esc_html(date_i18n('d/m/Y')); ?>.</p>
		<?php
		return ob_get_clean();
	}

	private static function privacy_en($settings, $settings_link) {
		$rows = self::processing_rows($settings, 'en');
		ob_start();
		?>
		<h3>Privacy notice</h3>
		<p>This notice explains how personal data collected through this website are processed under Regulation (EU) 2016/679.</p>
		<?php echo self::controller_block($settings, 'en'); ?>
		<p><strong>Data subjects:</strong> <?php echo esc_html($settings['data_subjects']); ?></p>
		<p><strong>Data categories:</strong> <?php echo esc_html($settings['data_categories']); ?></p>
		<p><strong>Data sources:</strong> <?php echo esc_html($settings['data_sources']); ?></p>
		<?php echo self::table('Purposes, legal bases and retention', array('Purpose', 'Data categories', 'Legal basis', 'Retention'), $rows); ?>
		<p><strong>Recipients and processors:</strong> <?php echo esc_html($settings['processors_summary']); ?></p>
		<p><strong>Processing locations:</strong> <?php echo esc_html($settings['processing_locations']); ?></p>
		<p><strong>Transfers outside the EEA:</strong> <?php echo esc_html(self::transfer_text($settings, 'en')); ?></p>
		<p><strong>General retention:</strong> <?php echo esc_html($settings['retention_general']); ?></p>
		<p><strong>Provision of data:</strong> <?php echo esc_html($settings['mandatory_data_note']); ?></p>
		<p><strong>Data subject rights:</strong> access, rectification, erasure, restriction, objection, portability where applicable, and withdrawal of consent without affecting previous lawful processing.</p>
		<p><strong>Complaint:</strong> data subjects may lodge a complaint with <?php echo esc_html($settings['complaint_authority_name']); ?><?php echo ! empty($settings['complaint_authority_url']) ? ' - '. esc_html($settings['complaint_authority_url']) : ''; ?>.</p>
		<p><strong>Automated decisions:</strong> <?php echo esc_html($settings['automated_decision_text']); ?></p>
		<p><strong>Children:</strong> <?php echo esc_html($settings['minors_data_text']); ?></p>
		<?php if (! empty($settings['custom_processing_notes'])) : ?>
			<p><strong>Additional notes:</strong> <?php echo esc_html($settings['custom_processing_notes']); ?></p>
		<?php endif; ?>
		<?php if ($settings_link) : ?><p>Cookie settings: <?php echo $settings_link; ?></p><?php endif; ?>
		<p class="lde-policy__updated">Last update: <?php echo esc_html(date_i18n('d/m/Y')); ?>.</p>
		<?php
		return ob_get_clean();
	}

	public static function cookie_policy_markup($settings, $scan, $cookies, $services, $include_title, $include_settings_link, $settings_link) {
		ob_start();
		?>
		<section class="simple-policy simple-policy--cookie">
			<?php if ($include_title) : ?><h2>Cookie policy</h2><?php endif; ?>
			<?php self::language_nav($settings); ?>
			<?php foreach (self::languages($settings) as $lang) : ?>
				<article class="simple-policy__language" lang="<?php echo esc_attr($lang); ?>">
					<?php if ('it' === $lang) : ?>
						<p>Questo sito usa cookie tecnici necessari al funzionamento, alla sicurezza e alla memorizzazione delle preferenze di consenso.</p>
						<p>Cookie e strumenti analoghi per preferenze, statistiche e marketing sono attivati solo dopo consenso, ove configurati.</p>
						<?php self::scan_note($scan, 'it'); ?>
						<?php echo self::cookie_tables($cookies, $services, 'it'); ?>
						<?php if ($include_settings_link) : ?><p>Puoi modificare le preferenze qui: <?php echo $settings_link; ?></p><?php endif; ?>
						<p>Titolare: <?php echo esc_html($settings['controller_legal_name'] ?: $settings['controller_name']); ?>.</p>
					<?php else : ?>
						<p>This website uses technical cookies required for operation, security and storage of consent preferences.</p>
						<p>Preference, analytics and marketing cookies or similar technologies are enabled only after consent, where configured.</p>
						<?php self::scan_note($scan, 'en'); ?>
						<?php echo self::cookie_tables($cookies, $services, 'en'); ?>
						<?php if ($include_settings_link) : ?><p>You can change your preferences here: <?php echo $settings_link; ?></p><?php endif; ?>
						<p>Controller: <?php echo esc_html($settings['controller_legal_name'] ?: $settings['controller_name']); ?>.</p>
					<?php endif; ?>
				</article>
			<?php endforeach; ?>
		</section>
		<?php
		return ob_get_clean();
	}

	private static function language_nav($settings) {
		$langs = self::languages($settings);
		if (count($langs) < 2) {
			return;
		}
		$labels = array('it' => 'Italiano', 'en' => 'English');
		echo '<p class="simple-policy__languages">';
		foreach ($langs as $lang) {
			echo '<span lang="'. esc_attr($lang) .'">'. esc_html($labels[$lang] ?? strtoupper($lang)) .'</span> ';
		}
		echo '</p>';
	}

	private static function controller_block($settings, $lang) {
		$title = 'it' === $lang ? 'Titolare del trattamento' : 'Data controller';
		$name = $settings['controller_legal_name'] ?: $settings['controller_name'];
		$parts = array_filter(array(
			$name,
			$settings['controller_address'] ?? '',
			! empty($settings['controller_tax_id']) ? 'CF: '. $settings['controller_tax_id'] : '',
			! empty($settings['controller_vat']) ? 'VAT/P.IVA: '. $settings['controller_vat'] : '',
			! empty($settings['controller_email']) ? 'Email: '. $settings['controller_email'] : '',
			! empty($settings['controller_pec']) ? 'PEC: '. $settings['controller_pec'] : '',
			! empty($settings['controller_phone']) ? 'Tel: '. $settings['controller_phone'] : '',
			! empty($settings['privacy_contact_email']) ? ('it' === $lang ? 'Contatto privacy: ' : 'Privacy contact: ') . $settings['privacy_contact_email'] : '',
			! empty($settings['dpo_email']) ? 'DPO/RPD: '. trim(($settings['dpo_name'] ?? '') .' '. $settings['dpo_email']) : '',
			! empty($settings['eu_representative']) ? ('it' === $lang ? 'Rappresentante UE: ' : 'EU representative: ') . $settings['eu_representative'] : '',
		));

		return '<p><strong>'. esc_html($title) .':</strong> '. esc_html(implode(' - ', $parts)) .'</p>';
	}

	private static function processing_rows($settings, $lang) {
		$labels = self::processing_labels($lang);
		$rows = array();
		foreach ($labels as $key => $label) {
			if ('1' !== ($settings['enable_'. $key] ?? '0')) {
				continue;
			}
			$rows[] = array(
				$label[0],
				$label[1],
				$settings['legal_basis_'. $key] ?? '',
				$settings['retention_'. $key] ?? '',
			);
		}

		return $rows;
	}

	private static function processing_labels($lang) {
		if ('en' === $lang) {
			return array(
				'contact_forms' => array('Handling contact requests', 'Contact details and message contents'),
				'newsletter' => array('Newsletter delivery', 'Name, email and subscription preferences'),
				'marketing' => array('Marketing and profiling', 'Contact details, consent preferences and online identifiers'),
				'analytics' => array('Analytics and audience measurement', 'Navigation data, device data and online identifiers'),
				'security' => array('Security, anti-spam and technical logs', 'IP address, log data and technical identifiers'),
				'ecommerce' => array('Orders, payments or donations', 'Identification, contact, payment and accounting data'),
				'legal_obligations' => array('Legal obligations and rights protection', 'Data required by applicable law or dispute management'),
			);
		}

		return array(
			'contact_forms' => array('Gestione richieste di contatto', 'Dati di contatto e contenuto del messaggio'),
			'newsletter' => array('Invio newsletter', 'Nome, email e preferenze di iscrizione'),
			'marketing' => array('Marketing e profilazione', 'Dati di contatto, preferenze consenso e identificativi online'),
			'analytics' => array('Statistiche e misurazione audience', 'Dati di navigazione, dispositivo e identificativi online'),
			'security' => array('Sicurezza, antispam e log tecnici', 'Indirizzo IP, log e identificativi tecnici'),
			'ecommerce' => array('Ordini, pagamenti o donazioni', 'Dati identificativi, contatto, pagamento e contabilita'),
			'legal_obligations' => array('Obblighi legali e tutela diritti', 'Dati richiesti dalla legge o dalla gestione di controversie'),
		);
	}

	private static function transfer_text($settings, $lang) {
		$mode = $settings['third_country_transfers'] ?? 'none';
		if ('none' === $mode) {
			return 'en' === $lang ? 'Not planned, unless strictly necessary for technical providers configured by the controller.' : 'Non previsti, salvo quanto strettamente necessario per fornitori tecnici configurati dal titolare.';
		}
		if ('possible' === $mode) {
			return ('en' === $lang ? 'Possible through technical providers. Safeguards: ' : 'Possibili tramite fornitori tecnici. Garanzie: ') . ($settings['transfer_safeguards'] ?? '');
		}
		return ('en' === $lang ? 'Planned. Safeguards: ' : 'Previsti. Garanzie: ') . ($settings['transfer_safeguards'] ?? '');
	}

	private static function scan_note($scan, $lang) {
		if (! empty($scan['scanned_at'])) {
			$text = 'en' === $lang ? 'Last technical cookie scan: ' : 'Ultima scansione tecnica dei cookie: ';
			echo '<p class="lde-policy__updated">'. esc_html($text . date_i18n('d/m/Y H:i', strtotime((string) $scan['scanned_at']))) .'.</p>';
			return;
		}

		$text = 'en' === $lang ? 'Technical scan not yet completed: the table shows the consent cookie installed by this website.' : 'Scansione tecnica non ancora eseguita: la tabella mostra il cookie tecnico di consenso installato da questo sito.';
		echo '<p class="lde-policy__updated">'. esc_html($text) .'</p>';
	}

	private static function cookie_tables($cookies, $services, $lang) {
		$cookie_columns = 'en' === $lang
			? array('Name', 'Provider', 'Category', 'Purpose', 'Duration', 'Source')
			: array('Nome', 'Fornitore', 'Categoria', 'Finalita', 'Durata', 'Fonte');

		$service_columns = 'en' === $lang
			? array('Service', 'Provider', 'Category', 'Purpose', 'Cookies/tools', 'Duration', 'Source')
			: array('Servizio', 'Fornitore', 'Categoria', 'Finalita', 'Cookie/strumenti', 'Durata', 'Fonte');

		$cookie_caption = 'en' === $lang ? 'Cookies detected by the scan' : 'Cookie rilevati dalla scansione';
		$service_caption = 'en' === $lang ? 'Services and scripts detected' : 'Servizi e script rilevati';

		$html = self::table($cookie_caption, $cookie_columns, array_map(static function($cookie) use ($lang) {
			return array(
				$cookie['name'] ?? '',
				$cookie['provider'] ?? '',
				self::translate_category($cookie['category'] ?? '', $lang),
				self::translate_purpose($cookie['purpose'] ?? '', $lang),
				$cookie['duration'] ?? '',
				self::source_label($cookie['source'] ?? '', $lang),
			);
		}, $cookies));

		if ($services) {
			$html .= self::table($service_caption, $service_columns, array_map(static function($service) use ($lang) {
				return array(
					$service['name'] ?? '',
					$service['provider'] ?? '',
					self::translate_category($service['category'] ?? '', $lang),
					self::translate_purpose($service['purpose'] ?? '', $lang),
					$service['cookies'] ?? '',
					$service['duration'] ?? '',
					self::source_label($service['source'] ?? '', $lang),
				);
			}, $services));
		}

		return $html;
	}

	private static function table($caption, $columns, $rows) {
		if (! $rows) {
			return '<p>Nessun elemento rilevato.</p>';
		}

		ob_start();
		?>
		<div class="lde-policy-table-wrapper">
			<table class="lde-policy-table">
				<caption><?php echo esc_html($caption); ?></caption>
				<thead>
					<tr>
						<?php foreach ($columns as $column) : ?>
							<th scope="col"><?php echo esc_html($column); ?></th>
						<?php endforeach; ?>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($rows as $row) : ?>
						<tr>
							<?php foreach ($row as $cell) : ?>
								<td><?php echo esc_html((string) $cell); ?></td>
							<?php endforeach; ?>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
		return ob_get_clean();
	}

	private static function translate_category($category, $lang) {
		if ('en' !== $lang) {
			return $category;
		}

		$map = array(
			'Necessari' => 'Necessary',
			'Preferenze' => 'Preferences',
			'Statistiche' => 'Analytics',
			'Marketing' => 'Marketing',
			'Da verificare' => 'To be verified',
			'Servizio esterno senza cookie HTTP rilevato' => 'External service without HTTP cookies detected',
			'Privacy/CMP legacy' => 'Legacy privacy/CMP',
		);

		return $map[$category] ?? $category;
	}

	private static function translate_purpose($purpose, $lang) {
		if ('en' !== $lang) {
			return $purpose;
		}

		$map = array(
			'Memorizza le preferenze di consenso cookie dell\'utente.' => 'Stores the user cookie consent preferences.',
			'Funzionamento tecnico, sicurezza, amministrazione o consenso.' => 'Technical operation, security, administration or consent.',
			'Misurazione statistica tramite Google Analytics.' => 'Analytics measurement through Google Analytics.',
			'Protezione anti-spam e sicurezza dei moduli.' => 'Anti-spam protection and form security.',
			'Ottimizzazione tecnica e cache del sito.' => 'Technical optimization and site cache.',
			'Cookie rilevato automaticamente: classificazione da confermare.' => 'Automatically detected cookie: classification to be confirmed.',
		);

		return $map[$purpose] ?? $purpose;
	}

	private static function source_label($source, $lang) {
		$source = (string) $source;
		if (! $source) {
			return 'en' === $lang ? 'N/A' : 'N/D';
		}

		if (filter_var($source, FILTER_VALIDATE_URL)) {
			$path = wp_parse_url($source, PHP_URL_PATH);
			if ($path && '/' !== $path) {
				return untrailingslashit($path);
			}
			return 'en' === $lang ? 'Homepage' : 'Homepage';
		}

		return $source;
	}
}
