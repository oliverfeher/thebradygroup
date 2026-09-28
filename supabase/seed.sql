-- Devin's real listings (source: Homes.com agent profile, Sept 2026). Run once after schema.sql.
-- Photos: upload in the admin (MLS photos belong to the listing photographer).
insert into public.listings (status, price, street, city, beds, baths, sqft, featured, description, zillow_url) values
  ('For sale', 165000, '719 S Chestnut Cir', 'Goldsboro, NC', 3, 2, 1248, true, 'A brick ranch with a functional layout and a spacious yard, minutes from downtown Goldsboro, shopping, dining and Seymour Johnson AFB.', 'https://www.homes.com/property/719-s-chestnut-cir-goldsboro-nc/37nxexb5efdsq/'),
  ('Sold', 219900, '419 Wapiti Dr', 'Spring Lake, NC', 3, 2, 1379, true, 'Sold in 4 days at 7% above list price.', 'https://www.homes.com/property/419-wapiti-dr-spring-lake-nc/xmw25j3q63l7k/'),
  ('Sold', 285000, '1016 Hooks Rd', 'Fremont, NC', 3, 2.5, 1607, true, 'Sold in 5 days. Devin represented both the seller and the buyer.', 'https://www.homes.com/property/1016-hooks-rd-fremont-nc/4wd62mgn16fz1/'),
  ('Sold', 214900, '900 Dandridge Dr', 'Fayetteville, NC', 3, 2, 1400, false, null, 'https://www.homes.com/property/900-dandridge-dr-fayetteville-nc/9w4vxk4yjtvcz/'),
  ('Sold', 255000, '131 Ryan''s Run Ln', 'Raeford, NC', 3, 2.5, 1744, false, null, 'https://www.homes.com/property/131-ryans-run-ln-raeford-nc/dl7x8g077pqw3/');
