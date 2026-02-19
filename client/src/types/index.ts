export interface Location {
  id: number
  name: string
  address: string | null
  timezone: string | null
  created_at: string
  updated_at: string
}

export interface User {
  id: number
  location_id: number
  name: string
  email: string
  role: 'admin' | 'manager' | 'server' | 'bartender'
  location?: Location
  created_at: string
  updated_at: string
}

export interface Category {
  id: number
  location_id: number
  name: string
  sort_order: number
  created_at: string
  updated_at: string
}

export interface MenuItem {
  id: number
  location_id: number
  category_id: number | null
  name: string
  description: string | null
  price: string
  type: string | null
  is_new: boolean
  is_active: boolean
  allergens: string[] | null
  category?: Category
  created_at: string
  updated_at: string
}

export interface EightySixed {
  id: number
  location_id: number
  menu_item_id: number | null
  item_name: string
  reason: string | null
  eighty_sixed_by: number
  restored_at: string | null
  menu_item?: MenuItem
  user?: User
  created_at: string
  updated_at: string
}

export interface Special {
  id: number
  location_id: number
  menu_item_id: number | null
  title: string
  description: string | null
  type: string | null
  starts_at: string
  ends_at: string | null
  is_active: boolean
  created_by: number
  menu_item?: MenuItem
  creator?: User
  created_at: string
  updated_at: string
}

export interface PushItem {
  id: number
  location_id: number
  menu_item_id: number | null
  title: string
  description: string | null
  reason: string | null
  priority: string | null
  is_active: boolean
  created_by: number
  menu_item?: MenuItem
  creator?: User
  created_at: string
  updated_at: string
}

export interface Announcement {
  id: number
  location_id: number
  title: string
  body: string | null
  priority: string | null
  target_roles: string[] | null
  posted_by: number
  expires_at: string | null
  poster?: User
  created_at: string
  updated_at: string
}

export interface Acknowledgment {
  id: number
  user_id: number
  acknowledgable_type: string
  acknowledgable_id: number
  acknowledged_at: string
}

export interface AcknowledgmentRef {
  type: string
  id: number
}

export interface PreShiftData {
  eighty_sixed: EightySixed[]
  specials: Special[]
  push_items: PushItem[]
  announcements: Announcement[]
  acknowledgments: AcknowledgmentRef[]
}
