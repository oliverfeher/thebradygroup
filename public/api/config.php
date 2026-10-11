<?php
// Secrets (Supabase secret key) live OUTSIDE the web root and out of git:
//   /home/customer/www/bradygrouphomes.com/tbg-secrets.php   (one level above public_html)
// It should contain:  <?php return ['supabase_url' => 'https://xxxx.supabase.co', 'supabase_key' => 'sb_secret_…'];
$secrets = @include dirname(__DIR__, 2) . '/tbg-secrets.php';
if (!is_array($secrets)) $secrets = [];

return $secrets + [
  'to'        => 'devinbrady77@gmail.com',
  'bcc'       => 'info@hustle-haus.com',
  // Must be a real mailbox on this domain (SiteGround → Email → Accounts); SiteGround refuses
  // PHP mail() "from" outside addresses like @gmail.com. Replies still go to the lead (Reply-To).
  'from'      => 'leads@bradygrouphomes.com',
  'from_name' => 'The Brady Group Website',
  'site_host' => 'bradygrouphomes.com',
  'crm_url'   => 'https://admin.bradygrouphomes.com/',
  'supabase_url' => '',
  'supabase_key' => '',
];
