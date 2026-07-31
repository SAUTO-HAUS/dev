-- ---------------------------------------------------------------------
-- Email as the sign-in identifier.
--
-- Signup no longer asks for a login; B2bAuth::register() writes the email into
-- `login` as well, so its UNIQUE index keeps guarding concurrent signups. The
-- column was VARCHAR(64) and has to hold an address (VARCHAR(190)).
--
-- create_b2b_tables.sql already declares the new width, so this ALTER is only
-- for a database where the B2B tables are already installed (testline).
--
-- Accounts created before this keep the login they picked: login() matches the
-- typed value against both `login` and `email`, so nobody is locked out.
-- ---------------------------------------------------------------------

ALTER TABLE `gh3sp_b2b_users`
    MODIFY `login` VARCHAR(190) NOT NULL;

-- Optional, only if you also want existing accounts to sign in with their email
-- as the login. Not required — they can already do that. Fails if two accounts
-- would end up with the same login, which cannot happen while `email` is unique.
-- UPDATE `gh3sp_b2b_users` SET `login` = `email`;
