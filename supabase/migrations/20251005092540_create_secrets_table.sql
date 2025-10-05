/*
  # Create secrets table

  1. New Tables
    - `secrets`
      - `id` (uuid, primary key) - Unique identifier for each secret
      - `user_id` (uuid) - Reference to the user who owns the secret
      - `title` (text) - Title or name of the secret
      - `content` (text) - The actual secret content
      - `created_at` (timestamptz) - When the secret was created
      - `updated_at` (timestamptz) - When the secret was last updated
  
  2. Security
    - Enable RLS on `secrets` table
    - Add policy for authenticated users to read their own secrets
    - Add policy for authenticated users to insert their own secrets
    - Add policy for authenticated users to update their own secrets
    - Add policy for authenticated users to delete their own secrets
*/

CREATE TABLE IF NOT EXISTS secrets (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  user_id uuid NOT NULL,
  title text NOT NULL,
  content text NOT NULL,
  created_at timestamptz DEFAULT now(),
  updated_at timestamptz DEFAULT now()
);

ALTER TABLE secrets ENABLE ROW LEVEL SECURITY;

CREATE POLICY "Users can view own secrets"
  ON secrets FOR SELECT
  TO authenticated
  USING (auth.uid() = user_id);

CREATE POLICY "Users can insert own secrets"
  ON secrets FOR INSERT
  TO authenticated
  WITH CHECK (auth.uid() = user_id);

CREATE POLICY "Users can update own secrets"
  ON secrets FOR UPDATE
  TO authenticated
  USING (auth.uid() = user_id)
  WITH CHECK (auth.uid() = user_id);

CREATE POLICY "Users can delete own secrets"
  ON secrets FOR DELETE
  TO authenticated
  USING (auth.uid() = user_id);