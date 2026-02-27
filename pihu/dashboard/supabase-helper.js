// Supabase Proxy Helper
const supabase = {
  async get(endpoint, params = {}) {
    const query = new URLSearchParams(params).toString();
    const url = `../api/proxy.php?endpoint=${endpoint}${query ? '&' + query : ''}`;
    const res = await fetch(url);
    return res.json();
  },
  
  async post(endpoint, data, params = {}) {
    const query = new URLSearchParams(params).toString();
    const url = `../api/proxy.php?endpoint=${endpoint}${query ? '&' + query : ''}`;
    const res = await fetch(url, {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify(data)
    });
    return res.json();
  },
  
  async patch(endpoint, data, params = {}) {
    const query = new URLSearchParams(params).toString();
    const url = `../api/proxy.php?endpoint=${endpoint}${query ? '&' + query : ''}`;
    const res = await fetch(url, {
      method: 'PATCH',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify(data)
    });
    return res.ok;
  },
  
  async delete(endpoint, params = {}) {
    const query = new URLSearchParams(params).toString();
    const url = `../api/proxy.php?endpoint=${endpoint}${query ? '&' + query : ''}`;
    const res = await fetch(url, {method: 'DELETE'});
    return res.ok;
  }
};
