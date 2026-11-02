"""
Input Validation Module - Common validation utilities for all services
Following TODO.md Input Validation requirements:
- String length limits (max 255 for names, max 5000 for descriptions)
- Email/phone/CCCD format validation
- Positive number checks (prices, quantities)
- DateTime format validation
- Enum validation (status, type, category)
"""
import re
from datetime import datetime
from typing import Any, List, Optional, Type


class ValidationError(Exception):
    """Custom validation error"""
    pass


class Validator:
    """Input validation utilities"""
    
    # Constants from TODO.md
    MAX_NAME_LENGTH = 255
    MAX_DESCRIPTION_LENGTH = 5000
    MAX_EMAIL_LENGTH = 255
    MAX_PHONE_LENGTH = 20
    MAX_CCCD_LENGTH = 20
    
    # Vietnamese phone patterns
    PHONE_PATTERNS = [
        r"^0[3-9]\d{8}$",  # 10 digits starting with 03-09
        r"^\+84[3-9]\d{8}$",  # +84 format
    ]
    
    # Vietnamese CCCD pattern (12 digits)
    CCCD_PATTERN = r"^\d{12}$"
    
    # Email pattern
    EMAIL_PATTERN = r"^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$"
    
    @staticmethod
    def validate_string_length(value: str, max_length: int, field_name: str) -> str:
        """Validate string length - max 255 for names, max 5000 for descriptions"""
        if not isinstance(value, str):
            raise ValidationError(f"{field_name} must be a string")
        
        if len(value) > max_length:
            raise ValidationError(f"{field_name} exceeds maximum length of {max_length} characters")
        
        return value.strip()
    
    @staticmethod
    def validate_name(value: str, field_name: str = "Name") -> str:
        """Validate name - max 255 characters"""
        return Validator.validate_string_length(value, Validator.MAX_NAME_LENGTH, field_name)
    
    @staticmethod
    def validate_description(value: str, field_name: str = "Description") -> str:
        """Validate description - max 5000 characters"""
        return Validator.validate_string_length(value, Validator.MAX_DESCRIPTION_LENGTH, field_name)
    
    @staticmethod
    def validate_email(email: str) -> str:
        """Validate email format"""
        if not email or not isinstance(email, str):
            raise ValidationError("Email is required")
        
        email = email.strip().lower()
        
        if len(email) > Validator.MAX_EMAIL_LENGTH:
            raise ValidationError(f"Email exceeds maximum length of {Validator.MAX_EMAIL_LENGTH}")
        
        if not re.match(Validator.EMAIL_PATTERN, email):
            raise ValidationError("Invalid email format")
        
        return email
    
    @staticmethod
    def validate_phone(phone: str) -> str:
        return Validator._validate_phone_impl(phone)

    @staticmethod
    def _validate_phone_impl(phone: str) -> str:
        """Validate Vietnamese phone number format"""
        if not phone or not isinstance(phone, str):
            raise ValidationError("Phone number is required")
        
        phone = re.sub(r'\s+', '', phone)  # Remove whitespace
        
        if len(phone) > Validator.MAX_PHONE_LENGTH:
            raise ValidationError(f"Phone exceeds maximum length of {Validator.MAX_PHONE_LENGTH}")
        
        # Check against patterns
        valid = False
        for pattern in Validator.PHONE_PATTERNS:
            if re.match(pattern, phone):
                valid = True
                break
        
        if not valid:
            raise ValidationError("Invalid phone number format. Use 0xxxxxxxxx or +84xxxxxxxxx")
        
        return phone
    
    @staticmethod
    def validate_cccd(cccd: str) -> str:
        """Validate Vietnamese CCCD (12 digits)"""
        if not cccd or not isinstance(cccd, str):
            raise ValidationError("CCCD is required")
        
        cccd = re.sub(r'\s+', '', cccd)  # Remove whitespace
        
        if len(cccd) > Validator.MAX_CCCD_LENGTH:
            raise ValidationError(f"CCCD exceeds maximum length of {Validator.MAX_CCCD_LENGTH}")
        
        if not re.match(Validator.CCCD_PATTERN, cccd):
            raise ValidationError("CCCD must be exactly 12 digits")
        
        return cccd
    
    @staticmethod
    def validate_positive_number(value: Any, field_name: str = "Amount") -> float:
        """Validate positive number - for prices, quantities"""
        try:
            num = float(value)
        except (TypeError, ValueError):
            raise ValidationError(f"{field_name} must be a number")
        
        if num < 0:
            raise ValidationError(f"{field_name} must be a positive number")
        
        return num
    
    @staticmethod
    def validate_positive_integer(value: Any, field_name: str = "Quantity") -> int:
        """Validate positive integer - for quantities"""
        try:
            num = int(value)
        except (TypeError, ValueError):
            raise ValidationError(f"{field_name} must be an integer")
        
        if num < 0:
            raise ValidationError(f"{field_name} must be a positive integer")
        
        return num
    
    @staticmethod
    def validate_price(value: Any, field_name: str = "Price") -> float:
        """Validate price - positive number with 2 decimal places"""
        price = Validator.validate_positive_number(value, field_name)
        
        # Check reasonable max (1 billion VND)
        if price > 1_000_000_000:
            raise ValidationError(f"{field_name} exceeds maximum allowed value")
        
        return round(price, 2)
    
    @staticmethod
    def validate_quantity(value: Any, field_name: str = "Quantity") -> int:
        """Validate quantity - positive integer"""
        return Validator.validate_positive_integer(value, field_name)
    
    @staticmethod
    def validate_datetime(value: Any, field_name: str = "DateTime") -> datetime:
        return Validator._validate_datetime_impl(value, field_name)

    @staticmethod
    def _validate_datetime_impl(value: Any, field_name: str = "DateTime") -> datetime:
        """Validate DateTime format"""
        if isinstance(value, datetime):
            return value
        
        if isinstance(value, str):
            try:
                # Try ISO format first
                return datetime.fromisoformat(value.replace('Z', '+00:00'))
            except ValueError:
                pass
            
            # Try other common formats
            formats = [
                "%Y-%m-%d %H:%M:%S",
                "%Y-%m-%d",
                "%d/%m/%Y %H:%M:%S",
                "%d/%m/%Y",
            ]
            
            for fmt in formats:
                try:
                    return datetime.strptime(value, fmt)
                except ValueError:
                    continue
        
        raise ValidationError(f"{field_name} must be a valid datetime format")
    
    @staticmethod
    def validate_enum(value: str, valid_values: List[str], field_name: str) -> str:
        """Validate enum value - for status, type, category"""
        if not value or not isinstance(value, str):
            raise ValidationError(f"{field_name} is required")
        
        value = value.strip().lower()
        
        # Case-insensitive comparison
        valid_lower = [v.lower() for v in valid_values]
        
        if value not in valid_lower:
            raise ValidationError(f"Invalid {field_name}. Must be one of: {', '.join(valid_values)}")
        
        # Return original case from valid values
        return valid_values[valid_lower.index(value)]
    
    @staticmethod
    def validate_status(value: str, valid_statuses: List[str]) -> str:
        """Validate status field"""
        return Validator.validate_enum(value, valid_statuses, "status")
    
    @staticmethod
    def validate_type(value: str, valid_types: List[str]) -> str:
        """Validate type field"""
        return Validator.validate_enum(value, valid_types, "type")
    
    @staticmethod
    def validate_category(value: str, valid_categories: List[str]) -> str:
        """Validate category field"""
        return Validator.validate_enum(value, valid_categories, "category")
    
    @staticmethod
    def validate_required(value: Any, field_name: str) -> Any:
        """Validate required field"""
        if value is None or (isinstance(value, str) and not value.strip()):
            raise ValidationError(f"{field_name} is required")
        return value
    
    @staticmethod
    def validate_optional_string(value: Optional[str], max_length: int) -> Optional[str]:
        """Validate optional string"""
        if value is None:
            return None
        return Validator.validate_string_length(value, max_length, "Optional field")


class InputValidator:
    """
    Input validation helper for API requests
    Usage:
        validator = InputValidator(data)
        name = validator.get_string("name", max_length=255, required=True)
        email = validator.get_email("email", required=True)
        price = validator.get_float("price", min_value=0, required=True)
    """
    
    def __init__(self, data: dict):
        self.data = data or {}
        self.errors: List[str] = []
    
    def get_string(self, key: str, max_length: int = 255, required: bool = False) -> Optional[str]:
        """Get and validate string"""
        value = self.data.get(key)
        
        if value is None or (isinstance(value, str) and not value.strip()):
            if required:
                self.errors.append(f"{key} is required")
            return None
        
        try:
            return Validator.validate_string_length(str(value), max_length, key)
        except ValidationError as e:
            self.errors.append(str(e))
            return None
    
    def get_name(self, key: str, required: bool = False) -> Optional[str]:
        """Get and validate name (max 255 chars)"""
        return self.get_string(key, max_length=Validator.MAX_NAME_LENGTH, required=required)
    
    def get_description(self, key: str, required: bool = False) -> Optional[str]:
        """Get and validate description (max 5000 chars)"""
        return self.get_string(key, max_length=Validator.MAX_DESCRIPTION_LENGTH, required=required)
    
    def get_email(self, key: str, required: bool = False) -> Optional[str]:
        """Get and validate email"""
        value = self.data.get(key)
        
        if value is None or not str(value).strip():
            if required:
                self.errors.append(f"{key} is required")
            return None
        
        try:
            return Validator.validate_email(str(value))
        except ValidationError as e:
            self.errors.append(str(e))
            return None
    
    def get_phone(self, key: str, required: bool = False) -> Optional[str]:
        """Get and validate phone"""
        value = self.data.get(key)
        
        if value is None or not str(value).strip():
            if required:
                self.errors.append(f"{key} is required")
            return None
        
        try:
            return Validator.validate_phone(str(value))
        except ValidationError as e:
            self.errors.append(str(e))
            return None
    
    def get_cccd(self, key: str, required: bool = False) -> Optional[str]:
        """Get and validate CCCD"""
        value = self.data.get(key)
        
        if value is None or not str(value).strip():
            if required:
                self.errors.append(f"{key} is required")
            return None
        
        try:
            return Validator.validate_cccd(str(value))
        except ValidationError as e:
            self.errors.append(str(e))
            return None
    
    def get_float(self, key: str, min_value: float = None, max_value: float = None, required: bool = False) -> Optional[float]:
        return self._get_float_impl(key, min_value, max_value, required)

    def _get_float_impl(self, key: str, min_value: float = None, max_value: float = None, required: bool = False) -> Optional[float]:
        """Get and validate float"""
        value = self.data.get(key)
        
        if value is None:
            if required:
                self.errors.append(f"{key} is required")
            return None
        
        try:
            num = float(value)
        except (TypeError, ValueError):
            self.errors.append(f"{key} must be a number")
            return None
        
        if min_value is not None and num < min_value:
            self.errors.append(f"{key} must be at least {min_value}")
            return None
        
        if max_value is not None and num > max_value:
            self.errors.append(f"{key} must be at most {max_value}")
            return None
        
        return num
    
    def get_price(self, key: str, required: bool = False) -> Optional[float]:
        """Get and validate price"""
        value = self.data.get(key)
        
        if value is None:
            if required:
                self.errors.append(f"{key} is required")
            return None
        
        try:
            return Validator.validate_price(value, key)
        except ValidationError as e:
            self.errors.append(str(e))
            return None
    
    def get_int(self, key: str, min_value: int = None, max_value: int = None, required: bool = False) -> Optional[int]:
        return self._get_int_impl(key, min_value, max_value, required)

    def _get_int_impl(self, key: str, min_value: int = None, max_value: int = None, required: bool = False) -> Optional[int]:
        """Get and validate integer"""
        value = self.data.get(key)
        
        if value is None:
            if required:
                self.errors.append(f"{key} is required")
            return None
        
        try:
            num = int(value)
        except (TypeError, ValueError):
            self.errors.append(f"{key} must be an integer")
            return None
        
        if min_value is not None and num < min_value:
            self.errors.append(f"{key} must be at least {min_value}")
            return None
        
        if max_value is not None and num > max_value:
            self.errors.append(f"{key} must be at most {max_value}")
            return None
        
        return num
    
    def get_quantity(self, key: str, required: bool = False) -> Optional[int]:
        """Get and validate quantity"""
        value = self.data.get(key)
        
        if value is None:
            if required:
                self.errors.append(f"{key} is required")
            return None
        
        try:
            return Validator.validate_quantity(value, key)
        except ValidationError as e:
            self.errors.append(str(e))
            return None
    
    def get_datetime(self, key: str, required: bool = False) -> Optional[datetime]:
        """Get and validate datetime"""
        value = self.data.get(key)
        
        if value is None:
            if required:
                self.errors.append(f"{key} is required")
            return None
        
        try:
            return Validator.validate_datetime(value, key)
        except ValidationError as e:
            self.errors.append(str(e))
            return None
    
    def get_enum(self, key: str, valid_values: List[str], required: bool = False) -> Optional[str]:
        """Get and validate enum"""
        value = self.data.get(key)
        
        if value is None or not str(value).strip():
            if required:
                self.errors.append(f"{key} is required")
            return None
        
        try:
            return Validator.validate_enum(str(value), valid_values, key)
        except ValidationError as e:
            self.errors.append(str(e))
            return None
    
    def get_status(self, key: str, valid_statuses: List[str], required: bool = False) -> Optional[str]:
        """Get and validate status"""
        value = self.data.get(key)
        
        if value is None or not str(value).strip():
            if required:
                self.errors.append(f"{key} is required")
            return None
        
        try:
            return Validator.validate_status(str(value), valid_statuses)
        except ValidationError as e:
            self.errors.append(str(e))
            return None
    
    def is_valid(self) -> bool:
        """Check if validation passed"""
        return len(self.errors) == 0
    
    def get_errors(self) -> List[str]:
        """Get all validation errors"""
        return self.errors