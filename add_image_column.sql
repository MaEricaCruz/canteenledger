-- Add image column to menu_items table
ALTER TABLE menu_items
ADD COLUMN image VARCHAR(255) DEFAULT NULL;

-- Update existing records to have NULL for image
UPDATE menu_items SET image = NULL; 