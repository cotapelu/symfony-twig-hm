from typing import Optional, List
from pydantic import BaseModel
from datetime import datetime

class Citizen(BaseModel):
    id: str
    citizen_id: str
    full_name: str
    date_of_birth: Optional[datetime]
    gender: Optional[str]
    address: Optional[str]
    phone: Optional[str]
    email: Optional[str]
    status: str = "active"
    created_at: Optional[datetime]
    updated_at: Optional[datetime]

class CitizenCreate(BaseModel):
    citizen_id: str
    full_name: str
    date_of_birth: Optional[datetime]
    gender: Optional[str]
    address: Optional[str]
    phone: Optional[str]
    email: Optional[str]

class CitizenService:
    def __init__(self, db):
        self.db = db
    
    async def create(self, data: CitizenCreate) -> Citizen:
        query = """
            INSERT INTO php.citizen 
            (citizen_id, full_name, date_of_birth, gender, address, phone, email)
            VALUES ($1, $2, $3, $4, $5, $6, $7)
            RETURNING id, citizen_id, full_name, date_of_birth, gender, address, phone, email, status, created_at, updated_at
        """
        row = await self.db.fetchrow(
            query, data.citizen_id, data.full_name, data.date_of_birth,
            data.gender, data.address, data.phone, data.email
        )
        return Citizen(**row) if row else None
    
    async def get_by_id(self, citizen_id: str) -> Optional[Citizen]:
        query = "SELECT * FROM php.citizen WHERE citizen_id = $1"
        row = await self.db.fetchrow(query, citizen_id)
        return Citizen(**row) if row else None
    
    async def search(self, query_str: str, page: int = 1, limit: int = 20) -> List[Citizen]:
        offset = (page - 1) * limit
        query = """
            SELECT * FROM php.citizen 
            WHERE full_name ILIKE $1 OR citizen_id ILIKE $1
            ORDER BY created_at DESC
            LIMIT $2 OFFSET $3
        """
        rows = await self.db.fetch(query, f"%{query_str}%", limit, offset)
        return [Citizen(**row) for row in rows]