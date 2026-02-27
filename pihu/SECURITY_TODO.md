# Security Fix Required

All dashboard pages need to be updated to use the proxy API instead of exposing Supabase keys.

## Files to update:
- index.php
- notifications.php  
- announcements.php
- updates.php
- users.php
- admins.php
- status.php

## Changes needed:
1. Add: `<script src="supabase-helper.js"></script>` after `<body>`
2. Replace all `fetch('<?= SUPABASE_URL ?>/rest/v1/...')` with `supabase.get/post/patch/delete()`
3. Remove all `SUPABASE_ANON_KEY` references from JavaScript

## Example (system.php already updated):
```javascript
// Old:
const res = await fetch('<?= SUPABASE_URL ?>/rest/v1/users?select=*', {
  headers: {
    'apikey': '<?= SUPABASE_ANON_KEY ?>',
    'Authorization': 'Bearer <?= SUPABASE_ANON_KEY ?>'
  }
});

// New:
const data = await supabase.get('users', {'select': '*'});
```

## Proxy API: `/pihu/api/proxy.php`
- Handles all Supabase requests server-side
- Keys never exposed to browser
- Session-based authentication
