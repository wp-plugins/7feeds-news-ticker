jQuery(document)
.ready(
function() {
  jQuery('#web_invoice_templates_tab_pane').tabs({cookie: { name: 'web_invoice_templates_tab_pane', expires: 30 } });
  jQuery('#web_invoice_settings_tab_pane').tabs({cookie: { name: 'web_invoice_settings_tab_pane', expires: 30 } });
}
);