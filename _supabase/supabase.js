  // ⬇️ paste your own values from Supabase → Project Settings → API
  const SUPABASE_URL = 'https://pvqpywgbonbsjadmbwui.supabase.co';
  const SUPABASE_ANON_KEY = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6InB2cXB5d2dib25ic2phZG1id3VpIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NjE1NDUyMDQsImV4cCI6MjA3NzEyMTIwNH0.lCVfB0qS3B2H8JidsFTIahgxWuKQ20H1HMZzHsrXSMw';

  // create a global client
  window.sb = supabase.createClient(SUPABASE_URL, SUPABASE_ANON_KEY);

  // tiny helpers
  window.sleep = (ms) => new Promise(r => setTimeout(r, ms));