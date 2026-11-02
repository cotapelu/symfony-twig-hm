"""Unit tests for app.validation.validators (input validation framework).

Tests security-relevant input validation: string lengths, email, phone, CCCD, prices, dates, enums.
"""
import pytest
from datetime import datetime

from app.validation.validators import Validator, InputValidator, ValidationError


# ==== validate_string_length ====

def test_validate_string_length_returns_stripped():
    result = Validator.validate_string_length("  hello  ", 100, "field")
    assert result == "hello"


def test_validate_string_length_valid_unchanged():
    assert Validator.validate_string_length("hello", 100, "f") == "hello"


def test_validate_string_length_too_long_raises():
    with pytest.raises(ValidationError, match="exceeds maximum length"):
        Validator.validate_string_length("a" * 256, 255, "Name")


def test_validate_string_length_non_string_raises():
    with pytest.raises(ValidationError, match="must be a string"):
        Validator.validate_string_length(123, 100, "field")


def test_validate_string_length_empty_string_returns_empty():
    assert Validator.validate_string_length("", 100, "f") == ""


def test_validate_string_length_exact_max_returns():
    assert Validator.validate_string_length("a" * 255, 255, "Name") == "a" * 255


# ==== validate_name ====

def test_validate_name_valid():
    assert Validator.validate_name("John Doe") == "John Doe"


def test_validate_name_max_length_ok():
    Validator.validate_name("a" * 255)


def test_validate_name_too_long_raises():
    with pytest.raises(ValidationError):
        Validator.validate_name("a" * 256)


# ==== validate_description ====

def test_validate_description_valid():
    assert Validator.validate_description("A" * 100) == "A" * 100


def test_validate_description_max_length_ok():
    Validator.validate_description("a" * 5000)


def test_validate_description_too_long_raises():
    with pytest.raises(ValidationError):
        Validator.validate_description("a" * 5001)


# ==== validate_email ====

def test_validate_email_valid_lowercases():
    assert Validator.validate_email("User@Example.COM") == "user@example.com"


def test_validate_email_strips_whitespace():
    assert Validator.validate_email("  user@example.com  ") == "user@example.com"


def test_validate_email_too_long_raises():
    with pytest.raises(ValidationError, match="exceeds maximum length"):
        Validator.validate_email("a" * 250 + "@x.com")


def test_validate_email_invalid_format_raises():
    with pytest.raises(ValidationError, match="Invalid email format"):
        Validator.validate_email("not-an-email")


def test_validate_email_missing_raises():
    with pytest.raises(ValidationError):
        Validator.validate_email("")


def test_validate_email_none_raises():
    with pytest.raises(ValidationError):
        Validator.validate_email(None)


def test_validate_email_non_string_raises():
    with pytest.raises(ValidationError):
        Validator.validate_email(123)


def test_validate_email_missing_at_sign_raises():
    with pytest.raises(ValidationError):
        Validator.validate_email("userexample.com")


def test_validate_email_missing_dot_raises():
    with pytest.raises(ValidationError):
        Validator.validate_email("user@example")


# ==== validate_phone ====

def test_validate_phone_valid_10_digits():
    assert Validator.validate_phone("0912345678") == "0912345678"


def test_validate_phone_valid_plus84():
    assert Validator.validate_phone("+84912345678") == "+84912345678"


def test_validate_phone_strips_whitespace():
    assert Validator.validate_phone("  091 234 5678  ") == "0912345678"


def test_validate_phone_too_long_raises():
    with pytest.raises(ValidationError, match="exceeds maximum length"):
        Validator.validate_phone("0" * 25)


def test_validate_phone_invalid_format_raises():
    with pytest.raises(ValidationError, match="Invalid phone"):
        Validator.validate_phone("12345")


def test_validate_phone_invalid_prefix_raises():
    """Phone starting with 01/02 (not 03-09) is invalid."""
    with pytest.raises(ValidationError):
        Validator.validate_phone("0112345678")


def test_validate_phone_empty_raises():
    with pytest.raises(ValidationError):
        Validator.validate_phone("")


def test_validate_phone_none_raises():
    with pytest.raises(ValidationError):
        Validator.validate_phone(None)


# ==== validate_cccd ====

def test_validate_cccd_valid_12_digits():
    assert Validator.validate_cccd("012345678901") == "012345678901"


def test_validate_cccd_strips_whitespace():
    assert Validator.validate_cccd("  012 345 678 901  ") == "012345678901"


def test_validate_cccd_too_long_raises():
    with pytest.raises(ValidationError, match="exceeds maximum length"):
        Validator.validate_cccd("0" * 25)


def test_validate_cccd_not_12_digits_raises():
    with pytest.raises(ValidationError, match="12 digits"):
        Validator.validate_cccd("01234567890")  # 11 digits


def test_validate_cccd_with_letters_raises():
    with pytest.raises(ValidationError):
        Validator.validate_cccd("01234567890a")


def test_validate_cccd_empty_raises():
    with pytest.raises(ValidationError):
        Validator.validate_cccd("")


# ==== validate_positive_number ====

def test_validate_positive_number_valid():
    assert Validator.validate_positive_number(10.5) == 10.5


def test_validate_positive_number_zero_allowed():
    assert Validator.validate_positive_number(0) == 0.0


def test_validate_positive_number_negative_raises():
    with pytest.raises(ValidationError, match="positive"):
        Validator.validate_positive_number(-1)


def test_validate_positive_number_string_int_converted():
    assert Validator.validate_positive_number("42") == 42.0


def test_validate_positive_number_string_invalid_raises():
    with pytest.raises(ValidationError, match="must be a number"):
        Validator.validate_positive_number("abc")


def test_validate_positive_number_none_raises():
    with pytest.raises(ValidationError):
        Validator.validate_positive_number(None)


# ==== validate_positive_integer ====

def test_validate_positive_integer_valid():
    assert Validator.validate_positive_integer(5) == 5


def test_validate_positive_integer_zero_allowed():
    assert Validator.validate_positive_integer(0) == 0


def test_validate_positive_integer_negative_raises():
    with pytest.raises(ValidationError, match="positive integer"):
        Validator.validate_positive_integer(-1)


def test_validate_positive_integer_string_converted():
    assert Validator.validate_positive_integer("42") == 42


def test_validate_positive_integer_string_invalid_raises():
    with pytest.raises(ValidationError):
        Validator.validate_positive_integer("abc")


# ==== validate_price ====

def test_validate_price_valid():
    assert Validator.validate_price(99.99) == 99.99


def test_validate_price_rounds_to_2_decimals():
    assert Validator.validate_price(99.9999) == 100.0


def test_validate_price_too_large_raises():
    with pytest.raises(ValidationError, match="exceeds maximum"):
        Validator.validate_price(1_000_000_001)


def test_validate_price_negative_raises():
    with pytest.raises(ValidationError):
        Validator.validate_price(-10)


# ==== validate_quantity ====

def test_validate_quantity_valid():
    assert Validator.validate_quantity(100) == 100


def test_validate_quantity_zero_allowed():
    assert Validator.validate_quantity(0) == 0


def test_validate_quantity_negative_raises():
    with pytest.raises(ValidationError):
        Validator.validate_quantity(-1)


def test_validate_quantity_float_string_raises():
    with pytest.raises(ValidationError):
        Validator.validate_quantity("1.5")  # not int


# ==== validate_datetime ====

def test_validate_datetime_already_datetime():
    dt = datetime.now()
    assert Validator.validate_datetime(dt) is dt


def test_validate_datetime_iso_format():
    result = Validator.validate_datetime("2024-01-15T10:30:00")
    assert result.year == 2024


def test_validate_datetime_iso_with_z():
    result = Validator.validate_datetime("2024-01-15T10:30:00Z")
    assert result.year == 2024


def test_validate_datetime_common_format():
    result = Validator.validate_datetime("2024-01-15 10:30:00")
    assert result.year == 2024


def test_validate_datetime_date_only():
    result = Validator.validate_datetime("2024-01-15")
    assert result.year == 2024
    assert result.hour == 0


def test_validate_datetime_dmy_format():
    result = Validator.validate_datetime("15/01/2024")
    assert result.year == 2024


def test_validate_datetime_invalid_raises():
    with pytest.raises(ValidationError, match="valid datetime"):
        Validator.validate_datetime("not a date")


def test_validate_datetime_none_raises():
    with pytest.raises(ValidationError):
        Validator.validate_datetime(None)


# ==== validate_enum ====

def test_validate_enum_valid():
    assert Validator.validate_enum("active", ["active", "inactive"], "status") == "active"


def test_validate_enum_case_insensitive():
    assert Validator.validate_enum("ACTIVE", ["active", "inactive"], "status") == "active"


def test_validate_enum_strips_whitespace():
    assert Validator.validate_enum("  active  ", ["active"], "status") == "active"


def test_validate_enum_invalid_raises():
    with pytest.raises(ValidationError, match="Invalid"):
        Validator.validate_enum("unknown", ["active", "inactive"], "status")


def test_validate_enum_empty_raises():
    with pytest.raises(ValidationError):
        Validator.validate_enum("", ["active"], "status")


def test_validate_enum_none_raises():
    with pytest.raises(ValidationError):
        Validator.validate_enum(None, ["active"], "status")


# ==== validate_required ====

def test_validate_required_valid_string():
    assert Validator.validate_required("hello", "f") == "hello"


def test_validate_required_empty_string_raises():
    with pytest.raises(ValidationError):
        Validator.validate_required("", "f")


def test_validate_required_whitespace_only_raises():
    with pytest.raises(ValidationError):
        Validator.validate_required("   ", "f")


def test_validate_required_none_raises():
    with pytest.raises(ValidationError):
        Validator.validate_required(None, "f")


def test_validate_required_zero_passes():
    """0 is a valid value, not 'missing'."""
    assert Validator.validate_required(0, "f") == 0


def test_validate_required_false_passes():
    """False is a valid value, not 'missing'."""
    assert Validator.validate_required(False, "f") is False


# ==== validate_optional_string ====

def test_validate_optional_string_none_returns_none():
    assert Validator.validate_optional_string(None, 100) is None


def test_validate_optional_string_empty_returns_empty():
    assert Validator.validate_optional_string("", 100) == ""


def test_validate_optional_string_valid():
    assert Validator.validate_optional_string("hello", 100) == "hello"


def test_validate_optional_string_too_long_raises():
    with pytest.raises(ValidationError):
        Validator.validate_optional_string("a" * 101, 100)


# ==== InputValidator class ====

def test_input_validator_init_empty():
    v = InputValidator({})
    assert v.data == {}
    assert v.errors == []


def test_input_validator_init_none_data():
    v = InputValidator(None)
    assert v.data == {}


def test_input_validator_get_string_valid():
    v = InputValidator({"name": "  John  "})
    assert v.get_string("name", max_length=100) == "John"
    assert v.errors == []


def test_input_validator_get_string_required_missing():
    v = InputValidator({})
    assert v.get_string("name", required=True) is None
    assert "name is required" in v.errors


def test_input_validator_get_string_too_long():
    v = InputValidator({"name": "a" * 300})
    assert v.get_string("name", max_length=255) is None
    assert len(v.errors) == 1


def test_input_validator_get_email_valid():
    v = InputValidator({"email": "user@example.com"})
    assert v.get_email("email") == "user@example.com"


def test_input_validator_get_email_required_missing():
    v = InputValidator({})
    assert v.get_email("email", required=True) is None
    assert "email is required" in v.errors


def test_input_validator_get_email_invalid():
    v = InputValidator({"email": "not-an-email"})
    assert v.get_email("email") is None
    assert "Invalid email format" in v.errors[0]


def test_input_validator_get_phone_valid():
    v = InputValidator({"phone": "0912345678"})
    assert v.get_phone("phone") == "0912345678"


def test_input_validator_get_phone_invalid():
    v = InputValidator({"phone": "123"})
    assert v.get_phone("phone") is None


def test_input_validator_get_cccd_valid():
    v = InputValidator({"cccd": "012345678901"})
    assert v.get_cccd("cccd") == "012345678901"


def test_input_validator_get_float_valid():
    v = InputValidator({"price": 99.99})
    assert v.get_float("price", min_value=0) == 99.99


def test_input_validator_get_float_below_min():
    v = InputValidator({"price": -1})
    assert v.get_float("price", min_value=0) is None
    assert "at least 0" in v.errors[0]


def test_input_validator_get_float_above_max():
    v = InputValidator({"price": 200})
    assert v.get_float("price", max_value=100) is None
    assert "at most 100" in v.errors[0]


def test_input_validator_get_float_non_numeric():
    v = InputValidator({"price": "abc"})
    assert v.get_float("price") is None
    assert "must be a number" in v.errors[0]


def test_input_validator_get_float_required_missing():
    v = InputValidator({})
    assert v.get_float("price", required=True) is None


def test_input_validator_get_int_valid():
    v = InputValidator({"quantity": 5})
    assert v.get_int("quantity", min_value=0) == 5


def test_input_validator_get_int_below_min():
    v = InputValidator({"quantity": -1})
    assert v.get_int("quantity", min_value=0) is None


def test_input_validator_get_int_above_max():
    v = InputValidator({"quantity": 200})
    assert v.get_int("quantity", max_value=100) is None


def test_input_validator_get_quantity_valid():
    v = InputValidator({"qty": 10})
    assert v.get_quantity("qty") == 10


def test_input_validator_get_datetime_valid():
    v = InputValidator({"date": "2024-01-15"})
    assert v.get_datetime("date") is not None


def test_input_validator_get_datetime_invalid():
    v = InputValidator({"date": "invalid"})
    assert v.get_datetime("date") is None


def test_input_validator_get_enum_valid():
    v = InputValidator({"status": "active"})
    assert v.get_enum("status", ["active", "inactive"]) == "active"


def test_input_validator_get_enum_invalid():
    v = InputValidator({"status": "unknown"})
    assert v.get_enum("status", ["active"]) is None


def test_input_validator_get_status_valid():
    v = InputValidator({"status": "pending"})
    assert v.get_status("status", ["pending", "approved"]) == "pending"


def test_input_validator_get_name_valid():
    v = InputValidator({"name": "John"})
    assert v.get_name("name") == "John"


def test_input_validator_get_name_too_long():
    v = InputValidator({"name": "a" * 300})
    assert v.get_name("name") is None


def test_input_validator_get_description_valid():
    v = InputValidator({"desc": "x" * 100})
    assert v.get_description("desc") == "x" * 100


def test_input_validator_is_valid_no_errors():
    v = InputValidator({"name": "John"})
    v.get_string("name")
    assert v.is_valid() is True


def test_input_validator_is_valid_with_errors():
    v = InputValidator({})
    v.get_string("name", required=True)
    assert v.is_valid() is False


def test_input_validator_multiple_errors_collected():
    v = InputValidator({})
    v.get_string("name", required=True)
    v.get_email("email", required=True)
    assert len(v.errors) == 2
    assert v.is_valid() is False


# ==== Integration scenarios ====

def test_full_validation_flow_valid():
    """All fields valid → no errors."""
    v = InputValidator({
        "name": "John Doe",
        "email": "john@example.com",
        "phone": "0912345678",
        "cccd": "012345678901",
        "price": 99.99,
        "quantity": 5,
    })
    v.get_name("name", required=True)
    v.get_email("email", required=True)
    v.get_phone("phone", required=False)
    v.get_cccd("cccd", required=False)
    v.get_price("price", required=True)
    v.get_quantity("quantity", required=True)
    assert v.is_valid() is True


def test_full_validation_flow_invalid():
    """Multiple invalid fields → all errors collected."""
    v = InputValidator({
        "name": "",  # empty
        "email": "not-email",  # invalid
        "phone": "123",  # invalid
    })
    v.get_name("name", required=True)
    v.get_email("email", required=True)
    v.get_phone("phone", required=True)
    assert v.is_valid() is False
    assert len(v.errors) >= 3
